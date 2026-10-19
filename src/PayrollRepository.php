<?php
declare(strict_types=1);

namespace Payroll;

use RuntimeException;

final class PayrollRepository
{
    private array $employees;
    private array $payroll;
    private array $employeeIndex = [];

    public function __construct(string $employeesPath, string $payrollPath)
    {
        $this->employees = CsvReader::read($employeesPath, ['id', 'full_name', 'email', 'position_id', 'department_id', 'manager_id']);
        foreach ($this->employees as &$employee) {
            foreach (['id', 'position_id', 'department_id', 'manager_id'] as $column) {
                $employee[$column] = $employee[$column] === '' || strtolower($employee[$column]) === 'null'
                    ? null : (int) $employee[$column];
            }
            if (isset($this->employeeIndex[$employee['id']])) {
                throw new RuntimeException('Duplicate employee ID.');
            }
            $this->employeeIndex[$employee['id']] = $employee;
        }
        unset($employee);
        $this->payroll = CsvReader::read($payrollPath, ['id', 'employee_id', 'pay_period_start', 'pay_period_end', 'gross_pay', 'net_pay']);
    }

    public function employees(): array { return $this->employees; }

    public function report(): array
    {
        $gross = 0;
        $net = 0;
        foreach ($this->payroll as $row) {
            $gross += Money::cents($row['gross_pay']);
            $net += Money::cents($row['net_pay']);
        }
        return [
            'total_payroll_records' => count($this->payroll),
            'total_gross_pay' => Money::number($gross),
            'total_deductions' => Money::number($gross - $net),
            'total_net_pay' => Money::number($net),
        ];
    }

    public function payslip(int $id): ?array
    {
        foreach ($this->payroll as $row) {
            if ((int) $row['id'] !== $id) { continue; }
            $employee = $this->employeeIndex[(int) $row['employee_id']] ?? null;
            if ($employee === null) { throw new RuntimeException('Payroll references a missing employee.'); }
            $gross = Money::cents($row['gross_pay']);
            $net = Money::cents($row['net_pay']);
            return [
                'payroll_id' => $id,
                'employee_id' => $employee['id'],
                'employee_name' => $employee['full_name'],
                'email' => $employee['email'],
                'department_id' => $employee['department_id'],
                'position_id' => $employee['position_id'],
                'pay_period_start' => $row['pay_period_start'],
                'pay_period_end' => $row['pay_period_end'],
                'gross_pay' => Money::number($gross),
                'deductions' => Money::number($gross - $net),
                'net_pay' => Money::number($net),
            ];
        }
        return null;
    }
}
