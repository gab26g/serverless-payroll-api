<?php
declare(strict_types=1);

use Aws\S3\S3Client;
use Payroll\Api;
use Payroll\PayrollRepository;
use Payroll\PayslipGenerator;
use Payroll\PayslipPdf;
use Payroll\S3Uploader;

ini_set('display_errors', '0');
require __DIR__ . '/vendor/autoload.php';

try {
    $repository = new PayrollRepository(__DIR__ . '/data/employees.csv', __DIR__ . '/data/payroll.csv');
    $api = new Api($repository, static function (array $slip): array {
        $bucket = getenv('PAYSLIP_BUCKET') ?: '';
        if ($bucket === '') { throw new RuntimeException('PAYSLIP_BUCKET must be configured.'); }
        $s3 = new S3Client([
            'version' => 'latest',
            'region' => getenv('AWS_REGION') ?: (getenv('AWS_DEFAULT_REGION') ?: 'us-east-1'),
        ]);
        $generator = new PayslipGenerator(new PayslipPdf(), new S3Uploader($s3, $bucket));
        return $generator->generate($slip);
    });
    $response = $api->handle($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
} catch (Throwable $error) {
    error_log('Payroll bootstrap failed: ' . get_class($error));
    $response = Api::error(500, 'Unable to load application data.');
}
http_response_code($response['status']);
foreach ($response['headers'] as $name => $value) { header($name . ': ' . $value); }
header('Cache-Control: no-store');
echo json_encode($response['body'], JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
