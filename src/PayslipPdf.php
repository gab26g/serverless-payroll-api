<?php
declare(strict_types=1);

namespace Payroll;

use Dompdf\Dompdf;
use Dompdf\Options;

final class PayslipPdf
{
    public function render(array $slip): string
    {
        $escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $name = $escape($slip['employee_name']);
        $id = $escape($slip['payroll_id']);
        $employeeId = $escape($slip['employee_id']);
        $email = $escape($slip['email']);
        $department = $escape($slip['department_id']);
        $position = $escape($slip['position_id']);
        $start = $escape($slip['pay_period_start']);
        $end = $escape($slip['pay_period_end']);
        $gross = number_format($slip['gross_pay'], 2, '.', ',');
        $deductions = number_format($slip['deductions'], 2, '.', ',');
        $net = number_format($slip['net_pay'], 2, '.', ',');
        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        // The Lambda application directory is read-only. Dompdf caches belong in /tmp.
        $options->set('tempDir', sys_get_temp_dir());
        $options->set('fontCache', sys_get_temp_dir());
        $pdf = new Dompdf($options);
        $pdf->loadHtml(<<<HTML
<!doctype html>
<html lang="en"><head><meta charset="UTF-8"><title>Payslip {$id}</title>
<style>
@page { margin: 48px; }
body { font-family: 'DejaVu Sans', sans-serif; color: #1d2939; font-size: 11px; line-height: 1.6; }
.label { color: #667085; font-size: 10px; text-transform: uppercase; letter-spacing: 1px; }
h1 { color: #163a5f; font-size: 30px; margin: 4px 0 0; }
.header { border-bottom: 3px solid #163a5f; padding-bottom: 18px; margin-bottom: 24px; }
h2 { font-size: 18px; margin-bottom: 4px; }
.details { width: 100%; margin: 16px 0 28px; border-collapse: collapse; }
.details td { width: 50%; padding: 6px 8px 6px 0; vertical-align: top; overflow-wrap: break-word; }
.amounts { width: 100%; border-collapse: collapse; }
.amounts th { background: #163a5f; color: white; text-align: left; padding: 11px 14px; }
.amounts td { padding: 13px 14px; border-bottom: 1px solid #d0d5dd; }
.amounts .right { text-align: right; }
.net { background: #edf4fa; font-weight: bold; font-size: 16px; }
.note { margin-top: 26px; color: #667085; font-size: 9px; }
</style></head><body>
<div class="header"><div class="label">Payroll management API / laboratory output</div><h1>Employee payslip</h1><div>Payroll record #{$id}</div></div>
<div class="label">Employee</div><h2>{$name}</h2><div>{$email}</div>
<table class="details">
<tr><td><span class="label">Employee ID</span><br>{$employeeId}</td><td><span class="label">Department ID / Position ID</span><br>{$department} / {$position}</td></tr>
<tr><td><span class="label">Pay period start</span><br>{$start}</td><td><span class="label">Pay period end</span><br>{$end}</td></tr>
</table>
<table class="amounts"><thead><tr><th>Description</th><th class="right">Amount</th></tr></thead><tbody>
<tr><td>Gross pay</td><td class="right">{$gross}</td></tr>
<tr><td>Deductions (gross pay - net pay)</td><td class="right">{$deductions}</td></tr>
<tr class="net"><td>Net pay</td><td class="right">{$net}</td></tr>
</tbody></table>
<p class="note">Source: Employees and Payroll sample dataset. Dates and amounts are reproduced as supplied, including any source anomalies. Currency is not specified by the dataset. This is a laboratory demonstration.</p>
</body></html>
HTML, 'UTF-8');
        $pdf->setPaper('A4');
        $pdf->render();
        return $pdf->output();
    }
}
