<?php
declare(strict_types=1);

use Aws\Exception\AwsException;
use Aws\MockHandler;
use Aws\Result;
use Aws\S3\S3Client;
use Payroll\Api;
use Payroll\PayslipGenerator;
use Payroll\PayslipPdf;
use Payroll\S3Uploader;

function s3Client(MockHandler $handler): S3Client {
    return new S3Client([
        'version'=>'latest', 'region'=>'us-east-1', 'handler'=>$handler,
        'credentials'=>['key'=>'test-key', 'secret'=>'test-secret'], 'retries'=>0,
    ]);
}

test('PDF renderer produces a PDF from the joined payslip', function (): void {
    $pdf = (new PayslipPdf())->render(repository()->payslip(12));
    truth(str_starts_with($pdf, '%PDF-'), 'Missing PDF signature');
    truth(strlen($pdf) > 3000, 'PDF is unexpectedly small');
});
test('generation sends real PDF bytes to S3 with correct key and content type', function (): void {
    $handler = new MockHandler([new Result(['ETag'=>'"test-etag"'])]);
    $generator = new PayslipGenerator(new PayslipPdf(), new S3Uploader(s3Client($handler), 'example-payroll-bucket'));
    $result = $generator->generate(repository()->payslip(12));
    same(12, $result['payroll_id']); same('payslips/12.pdf', $result['payslip_key']);
    same('https://example-payroll-bucket.s3.amazonaws.com/payslips/12.pdf', $result['payslip_file']);
    $request = $handler->getLastRequest();
    same('PUT', $request->getMethod()); same('/payslips/12.pdf', $request->getUri()->getPath());
    same('application/pdf', $request->getHeaderLine('Content-Type'));
    truth(str_starts_with((string) $request->getBody(), '%PDF-'), 'Uploaded content is not PDF');
    same('', $request->getHeaderLine('x-amz-acl'));
});
test('S3 denial becomes an API failure instead of a false upload success', function (): void {
    $handler = new MockHandler([new AwsException('Denied', new Aws\Command('PutObject'), ['code'=>'AccessDenied'])]);
    $generator = new PayslipGenerator(new PayslipPdf(), new S3Uploader(s3Client($handler), 'example-payroll-bucket'));
    $api = new Api(repository(), $generator->generate(...));
    $response = $api->handle('POST', '/api/payslips/12/generate');
    same(500, $response['status']); same(false, $response['body']['success']);
    truth(!isset($response['body']['data']), 'Failed upload returned a file');
});
test('missing bucket fails before an upload attempt', function (): void {
    $handler = new MockHandler([]);
    raises(fn () => new S3Uploader(s3Client($handler), ''), RuntimeException::class);
    same(null, $handler->getLastRequest());
});
