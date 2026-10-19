<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$id = filter_var($argv[1] ?? '1', FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
if ($id === false) { fwrite(STDERR, "Supply a positive payroll ID.\n"); exit(1); }
$repository = new Payroll\PayrollRepository(__DIR__ . '/../data/employees.csv', __DIR__ . '/../data/payroll.csv');
$slip = $repository->payslip($id);
if ($slip === null) { fwrite(STDERR, "Payroll record not found.\n"); exit(1); }
$directory = __DIR__ . '/../output/pdf';
if (!is_dir($directory)) { mkdir($directory, 0777, true); }
$path = $directory . '/payslip-' . $id . '.pdf';
file_put_contents($path, (new Payroll\PayslipPdf())->render($slip));
echo realpath($path) . "\n";
