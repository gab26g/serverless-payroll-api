<?php
declare(strict_types=1);

$autoload = __DIR__ . '/../vendor/autoload.php';
if (is_file($autoload)) {
    require $autoload;
} else {
    spl_autoload_register(function (string $class): void {
        if (str_starts_with($class, 'Payroll\\')) {
            $file = __DIR__ . '/../src/' . substr($class, 8) . '.php';
            if (is_file($file)) { require $file; }
        }
    });
}

use Payroll\Api;
use Payroll\CsvReader;
use Payroll\Money;
use Payroll\PayrollRepository;

$tests = [];
function test(string $name, Closure $run): void { global $tests; $tests[$name] = $run; }
function same(mixed $expected, mixed $actual): void {
    if ($expected !== $actual) {
        throw new RuntimeException('Expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
    }
}
function truth(bool $condition, string $message): void { if (!$condition) { throw new RuntimeException($message); } }
function raises(Closure $run, string $type): void {
    try { $run(); } catch (Throwable $error) {
        truth($error instanceof $type, 'Unexpected exception: ' . get_class($error));
        return;
    }
    throw new RuntimeException('Expected exception ' . $type);
}
function fixture(string $contents): string {
    $path = tempnam(sys_get_temp_dir(), 'payroll-test-');
    file_put_contents($path, $contents);
    register_shutdown_function(static fn () => is_file($path) ? unlink($path) : null);
    return $path;
}
function repository(): PayrollRepository {
    return new PayrollRepository(
        fixture("id,full_name,email,position_id,department_id,manager_id\n7,\"Doe, Jane\",jane@example.com,2,3,\n"),
        fixture("id,employee_id,pay_period_start,pay_period_end,gross_pay,net_pay\n12,7,2023-02-28,2023-02-01,100.10,90.05\n13,7,2023-03-01,2023-03-31,0.20,0.30\n")
    );
}

test('decimal parsing is exact including negative amounts', function (): void {
    same(10010, Money::cents('100.10'));
    same(20, Money::cents('0.20'));
    same(-10, Money::cents('-0.10'));
    same(100, Money::cents('1'));
    same(120, Money::cents('1.2'));
    same(0.3, Money::number(30));
    raises(fn () => Money::cents('oops'), RuntimeException::class);
    raises(fn () => Money::cents('1.001'), RuntimeException::class);
});
test('CSV reader preserves quoted commas and ignores blank lines', function (): void {
    $rows = CsvReader::read(fixture("\xEF\xBB\xBFid,full_name\n1,\"Doe, Jane\"\n\n"), ['id', 'full_name']);
    same([['id' => '1', 'full_name' => 'Doe, Jane']], $rows);
});
test('CSV reader rejects missing headers and malformed rows', function (): void {
    raises(fn () => CsvReader::read(fixture("id\n1\n"), ['id', 'gross_pay']), RuntimeException::class);
    raises(fn () => CsvReader::read(fixture("id,name\n1\n"), ['id']), RuntimeException::class);
    raises(fn () => CsvReader::read(fixture("id,id\n1,2\n"), ['id']), RuntimeException::class);
    raises(fn () => CsvReader::read(__DIR__ . '/missing.csv', ['id']), RuntimeException::class);
});
test('employees retain CSV fields and use numeric IDs and nullable manager', function (): void {
    same(['id'=>7,'full_name'=>'Doe, Jane','email'=>'jane@example.com','position_id'=>2,'department_id'=>3,'manager_id'=>null], repository()->employees()[0]);
});
test('report totals include all rows with exact signed deductions', function (): void {
    same(['total_payroll_records'=>2,'total_gross_pay'=>100.3,'total_deductions'=>9.95,'total_net_pay'=>90.35], repository()->report());
});
test('payslip joins payroll ID to employee and preserves supplied dates', function (): void {
    $slip = repository()->payslip(12);
    same(12, $slip['payroll_id']); same(7, $slip['employee_id']);
    same('Doe, Jane', $slip['employee_name']); same('jane@example.com', $slip['email']);
    same('2023-02-28', $slip['pay_period_start']); same('2023-02-01', $slip['pay_period_end']);
    same(100.1, $slip['gross_pay']); same(10.05, $slip['deductions']); same(90.05, $slip['net_pay']);
    same(-0.1, repository()->payslip(13)['deductions']);
    same(null, repository()->payslip(999));
});
test('read routes return success envelopes and JSON content type', function (): void {
    $api = new Api(repository());
    foreach (['/api/employees?ignored=1', '/api/reports/payroll', '/api/payslips/12'] as $path) {
        $response = $api->handle('GET', $path);
        same(200, $response['status']); same(true, $response['body']['success']);
        same('application/json; charset=utf-8', $response['headers']['Content-Type']);
    }
});
test('invalid IDs missing IDs unknown routes and wrong methods have useful statuses', function (): void {
    $api = new Api(repository());
    foreach (['abc','0','-1','1.5','9999999999999999999999999','012'] as $id) {
        same(400, $api->handle('GET', '/api/payslips/' . $id)['status']);
    }
    same(404, $api->handle('GET', '/api/payslips/999')['status']);
    same(404, $api->handle('GET', '/nope')['status']);
    $response = $api->handle('POST', '/api/employees');
    same(405, $response['status']); same('GET', $response['headers']['Allow']);
    same(false, $response['body']['success']);
});
test('generate route uses matching payslip and returns key and file URL', function (): void {
    $api = new Api(repository(), function (array $slip): array {
        same(12, $slip['payroll_id']); same('Doe, Jane', $slip['employee_name']);
        return ['payroll_id'=>12,'payslip_key'=>'payslips/12.pdf','payslip_file'=>'https://example.test/payslips/12.pdf'];
    });
    $response = $api->handle('POST', '/api/payslips/12/generate');
    same(200, $response['status']); same('Payslip generated and uploaded.', $response['body']['message']);
    same('payslips/12.pdf', $response['body']['data']['payslip_key']);
    same(404, $api->handle('POST', '/api/payslips/999/generate')['status']);
});
test('generation failure returns generic JSON error without leaking secrets', function (): void {
    $api = new Api(repository(), fn () => throw new RuntimeException('private diagnostic'));
    $response = $api->handle('POST', '/api/payslips/12/generate');
    same(500, $response['status']); same(false, $response['body']['success']);
    truth(!str_contains(json_encode($response['body']), 'private diagnostic'), 'Error leaked');
});
test('official CSV totals match independent decimal calculation', function (): void {
    $repo = new PayrollRepository(__DIR__ . '/../data/employees.csv', __DIR__ . '/../data/payroll.csv');
    same(500, count($repo->employees()));
    same(['total_payroll_records'=>600,'total_gross_pay'=>4981004.63,'total_deductions'=>849478.70,'total_net_pay'=>4131525.93], $repo->report());
    $slip = $repo->payslip(1);
    same('Deniz Fischer', $slip['employee_name']); same(319, $slip['employee_id']);
    same(14131.54, $slip['gross_pay']); same(12626.36, $slip['deductions']); same(1505.18, $slip['net_pay']);
});

require __DIR__ . '/pdf-upload.php';

$failed = 0;
foreach ($tests as $name => $run) {
    try { $run(); echo "PASS $name\n"; }
    catch (Throwable $error) { $failed++; echo "FAIL $name: {$error->getMessage()}\n"; }
}
echo count($tests) . ' tests, ' . $failed . " failures\n";
exit($failed === 0 ? 0 : 1);
