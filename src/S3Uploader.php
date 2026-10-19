<?php
declare(strict_types=1);

namespace Payroll;

use Aws\S3\S3Client;
use RuntimeException;

final class S3Uploader
{
    public function __construct(private S3Client $client, private string $bucket)
    {
        if ($bucket === '') { throw new RuntimeException('PAYSLIP_BUCKET must be configured.'); }
    }

    public function upload(int $payrollId, string $pdf): array
    {
        $key = 'payslips/' . $payrollId . '.pdf';
        $this->client->putObject([
            'Bucket' => $this->bucket,
            'Key' => $key,
            'Body' => $pdf,
            'ContentType' => 'application/pdf',
        ]);
        return [
            'payroll_id' => $payrollId,
            'payslip_key' => $key,
            'payslip_file' => $this->client->getObjectUrl($this->bucket, $key),
        ];
    }
}
