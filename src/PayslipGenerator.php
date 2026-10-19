<?php
declare(strict_types=1);

namespace Payroll;

final class PayslipGenerator
{
    public function __construct(private PayslipPdf $pdf, private S3Uploader $uploader) {}

    public function generate(array $slip): array
    {
        return $this->uploader->upload($slip['payroll_id'], $this->pdf->render($slip));
    }
}
