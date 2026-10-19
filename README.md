# Serverless Payroll Management API

Plain PHP 8.2 + Bref 3 + AWS Lambda + API Gateway HTTP API. Employee and payroll records come from bundled CSV files; no database is used. Dompdf generates payslips and the AWS SDK uploads PDFs to a private S3 bucket.

## Current status

**Deployed and verified on 2026-10-19.** All four live endpoints return HTTP 200. The generated PDF was uploaded to private S3 storage, downloaded, and checked for readable layout and correct employee/payroll figures.

**API base URL:** https://51fjkdjyef.execute-api.ap-southeast-2.amazonaws.com

Stack: `gab-payroll-api-dev`, Sydney (`ap-southeast-2`). Payslip bucket: `sam10-payroll-payslips-tabing`. Deployment used the existing shared console session through [CloudShell](docs/CLOUDSHELL.md), without creating shared keys or adding policies to the student user. See [verified deployment details](docs/DEPLOYMENT-STATUS.md).

Repository: https://github.com/gab26g/serverless-payroll-api

Start with [the remaining setup and submission steps](docs/FINISH.md). See [verification results and limits](docs/VERIFICATION.md).

Live GET links: [employees](https://51fjkdjyef.execute-api.ap-southeast-2.amazonaws.com/api/employees), [payroll report](https://51fjkdjyef.execute-api.ap-southeast-2.amazonaws.com/api/reports/payroll), [payslip #1](https://51fjkdjyef.execute-api.ap-southeast-2.amazonaws.com/api/payslips/1).

## Endpoints

| Method | Path | Result |
|---|---|---|
| GET | `/api/employees` | All employee records, preserving CSV field names |
| GET | `/api/reports/payroll` | Record count and total gross, deductions and net |
| GET | `/api/payslips/{payroll_id}` | Employee details and one payroll record |
| POST | `/api/payslips/{payroll_id}/generate` | Generate a PDF, upload it, return its key and object URL |

IDs refer to `payroll.id`, not `employee_id`. Positive integer IDs without leading zeros are accepted. A missing record returns 404, an invalid ID returns 400, an unsupported method returns 405 with `Allow`, and processing/upload failures return a generic JSON error with HTTP 500. No POST body is required.

Example generation response (illustrative bucket):

```json
{
  "success": true,
  "data": {
    "payroll_id": 1,
    "payslip_key": "payslips/1.pdf",
    "payslip_file": "https://example-payroll-bucket.s3.amazonaws.com/payslips/1.pdf"
  },
  "message": "Payslip generated and uploaded."
}
```

The object URL is **not a public download link**. Open/download the PDF through the authenticated S3 console. Regenerating the same payroll ID writes the same object key.

## Local setup (PowerShell)

Requirements: PHP 8.2, Composer, Node.js/npm, and PHP extensions curl, dom, mbstring, openssl, and fileinfo. These were available on the activity computer. No AWS CLI or XAMPP Apache/MySQL service is needed.

```powershell
composer install --no-interaction --prefer-dist
npm.cmd ci
composer test
php -S 127.0.0.1:8080 index.php
```

Keep that terminal open. In a second terminal in the same folder:

```powershell
.\tools\smoke-test.ps1 -BaseUrl http://127.0.0.1:8080
php tools/preview-payslip.php 1
```

The preview is saved to `output/pdf/payslip-1.pdf`. The three read endpoints and offline preview do not require AWS credentials. The POST upload endpoint requires a bucket and credentials with upload permission; it intentionally returns 500 when these are unavailable. Automated upload tests use the real AWS SDK with a mocked network boundary, not a live bucket.

## AWS deployment

The live deployment used CloudShell with the existing shared console identity. Follow [CLOUDSHELL.md](docs/CLOUDSHELL.md) for that route. The local commands below require a deployment profile with provisioning permissions; the student `payroll-lab` profile's Lambda/S3 policies alone do not supply them.

1. Create a private general-purpose S3 bucket in the intended region. Use SSE-S3 encryption, disabled ACLs and enabled Block Public Access.
2. Configure your school-approved deployment credentials outside Git. The optional helper prompts for hidden values and appends a new named profile to your user-level `.aws/credentials` file:

```powershell
.\tools\configure-aws.ps1
```

3. After obtaining the professor-approved deployment permissions, deploy to the verified bucket:

```powershell
.\tools\deploy.ps1 -Bucket sam10-payroll-payslips-tabing -Region ap-southeast-2
```

The helper defaults to the `payroll-lab` credential profile, verifies the school AWS account using STS, runs the test suite, then deploys. Use `-Profile NAME` to choose another profile. Credentials in `AWS_ACCESS_KEY_ID`/`AWS_SECRET_ACCESS_KEY` environment variables take precedence in the AWS SDK; use a terminal configured for the intended school account. Temporary credentials also need `AWS_SESSION_TOKEN`.

The IAM deployer needs permission to provision/update CloudFormation, Lambda, API Gateway, CloudWatch logs, deployment S3 storage, and the execution role, including `iam:PassRole`. The Lambda execution role separately receives only `s3:PutObject` on the selected bucket's `payslips/*` prefix plus its logging permissions. An explicit deny or school permission boundary can still block deployment.

If your instructor supplies an existing execution role and prohibits role creation, replace `provider.iam.role` (the entire map including `statements`) in `serverless.yml` with that role ARN. Its administrator must attach the upload permission and Lambda logging permissions, and the deployer still needs `iam:PassRole`. Do not grant broad administrator access to work around lab restrictions.

Equivalent manual deployment, after configuring credentials:

```powershell
$env:AWS_PROFILE = 'payroll-lab'
$env:PAYSLIP_BUCKET = 'sam10-payroll-payslips-tabing'
npx.cmd serverless deploy --region ap-southeast-2
```

Copy the HTTP API URL printed by Serverless. Use it as the base URL, without adding `/api` until the endpoint path.

## Package without deploying

```powershell
$env:PAYSLIP_BUCKET = 'example-payroll-bucket'
npm.cmd run package
```

The placeholder is for offline package checks only. Set the real bucket before deployment. The deployment ZIP includes `vendor/`, code and CSVs, but excludes Node tooling, tests, credential files and output artifacts. Bref configures the Amazon Linux 2023 custom runtime and PHP 8.2 FPM layer. PDF temporary files and font caches use Lambda's writable temporary directory.

## Live verification and evidence

```powershell
.\tools\smoke-test.ps1 -BaseUrl https://51fjkdjyef.execute-api.ap-southeast-2.amazonaws.com -GeneratePdf
```

The script checks all four responses and stores their JSON in ignored `output/evidence/`. It does not capture screenshots. Verify `payslips/1.pdf` in S3 and capture the evidence listed in [FINISH.md](docs/FINISH.md).

## Dataset and expected totals

Dataset version 3 contains 500 employees and 600 payroll records. Preserve all source records. Report totals:

| Gross | Deductions | Net |
|---|---|---|
| 4,981,004.63 | 849,478.70 | 4,131,525.93 |

Deductions are gross minus net. Arithmetic is performed in integer cents. Some supplied records have reversed dates or negative deductions. No source rows are silently corrected or omitted. The dataset does not specify a currency. See [data provenance](data/SOURCE.md).

## Project structure

- `index.php`: HTTP entry point, environment setup, JSON response output.
- `src/`: CSV reader, cent arithmetic, payroll joins/report, routing, PDF rendering and S3 upload.
- `data/`: required original CSVs and provenance.
- `serverless.yml`: Lambda/API Gateway infrastructure and upload permission.
- `tests/`: self-contained PHP behavior tests with AWS SDK network mocks.
- `tools/`: private credential setup, deployment, HTTP smoke testing, offline PDF preview.
- `docs/`: remaining steps, verification record and submission draft.

## Known limits

This is a laboratory API using synthetic data; no authentication or production payroll rules are implemented. The PDF-generation endpoint can be invoked by anyone who knows the deployed API URL, so keep the deployment for the activity and remove it after grading when permitted. Objects remain private.

The installed OSS Serverless development tool has an npm advisory inherited through `braces`/`micromatch`/`fast-glob` (GHSA-vfj7-8cjw-p6xm); npm reported no available fix during setup. It concerns deeply nested glob patterns. Packaging patterns here are fixed developer-controlled strings, and Node packages are not deployed to Lambda. Recheck `npm.cmd audit` when updating tooling. Do not use `--force` to apply an unreviewed dependency change.

## References

- [Bref setup](https://bref.sh/docs/setup)
- [Bref PHP deployment guide](https://bref.sh/docs/default/getting-started)
- [Dataset](https://databasesample.com/datasets/employees-and-payroll), CC0 1.0
- [Dompdf](https://github.com/dompdf/dompdf)
- [AWS SDK for PHP](https://docs.aws.amazon.com/sdk-for-php/v3/developer-guide/welcome.html)
