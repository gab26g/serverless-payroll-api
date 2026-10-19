# Dataset provenance

Source: https://databasesample.com/datasets/employees-and-payroll

Version: 3. License: CC0 1.0 Public Domain. Downloaded 2026-10-19.

- https://database-sample-website.s3.eu-north-1.amazonaws.com/datasets/employees-and-payroll/v3/csv/employees.csv
- https://database-sample-website.s3.eu-north-1.amazonaws.com/datasets/employees-and-payroll/v3/csv/payroll.csv

The original files are preserved without edits: 500 employees and 600 payroll records.
Some source pay-period start dates are later than their end dates. Some net pay amounts exceed gross pay, producing negative derived deductions. The API deliberately preserves these records and uses gross minus net as instructed. Currency is not specified; no currency symbol is invented.

Independent verification with PowerShell decimal arithmetic:

| Payroll records | Gross | Deductions | Net |
|---|---|---|---|
| 600 | 4,981,004.63 | 849,478.70 | 4,131,525.93 |
