# Deployment complete - 2026-10-19

| Item | Verified value |
|---|---|
| Repository | https://github.com/gab26g/serverless-payroll-api |
| API base URL | https://51fjkdjyef.execute-api.ap-southeast-2.amazonaws.com |
| Region | Asia Pacific (Sydney), `ap-southeast-2` |
| CloudFormation stack | `gab-payroll-api-dev`, `UPDATE_COMPLETE` |
| Lambda | `gab-payroll-api-dev-web`, Active |
| Runtime | Bref PHP 8.2 FPM, `provided.al2023` |
| Execution role | `gab-payroll-api-dev-ap-southeast-2-lambdaRole` |
| Payslip bucket | `sam10-payroll-payslips-tabing` |
| Generated PDF | `payslips/1.pdf`, application/pdf, SSE-S3 |

## Deployment method

The new `payroll-lab` profile authenticated correctly but lacked CloudFormation/API Gateway provisioning permissions. The existing shared console login `lambda` already had deployment access. CloudShell used that console identity without requiring another shared access key or extra policies on the student user.

The PHP application was packaged locally and uploaded as a portable bundle. Its SHA256 was checked after upload. CloudShell installed OSS Serverless 4.4.0, validated the template (CAPABILITY_NAMED_IAM), then deployed the prepared package after the user confirmed the concrete resource/role creation. Serverless reported success in 63 seconds. See [CLOUDSHELL.md](CLOUDSHELL.md) for the workflow.

## Live verification

- All four endpoints returned HTTP 200 with success=true.
- Employee count and payroll totals match the original CSVs.
- Invalid ID, missing record, wrong method and unknown route return expected JSON errors (400/404/405/404).
- S3 object exists, uses content type application/pdf and SSE-S3, and was downloaded and rendered for text/layout checks.
- Payroll #1 contains Deniz Fischer (employee #319), gross 14,131.54, deductions 12,626.36 and net 1,505.18.
- The actual role policy restricts S3 uploads to `arn:aws:s3:::sam10-payroll-payslips-tabing/payslips/*` and only permits application logging for this project's log groups.
- Bucket public access remains blocked and ACLs remain disabled.

Screenshots, raw responses and the downloaded PDF are available in local `output/`; generated artifacts are intentionally excluded from Git. The remaining student steps are reviewing/personalizing the reflection and submitting the prepared materials on the course page.
