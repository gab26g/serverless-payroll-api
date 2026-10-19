# Deployment status - 2026-10-19

## Verified

- GitHub: https://github.com/gab26g/serverless-payroll-api
- Local AWS profile: `payroll-lab`, authenticated successfully as `gab-payroll-deployer` in the school account.
- Payslip bucket: `sam10-payroll-payslips-tabing`.
- Bucket region: **Asia Pacific (Sydney), `ap-southeast-2`**.
- All four S3 Block Public Access settings are enabled; bucket ownership is BucketOwnerEnforced (ACLs disabled); default encryption is SSE-S3.
- Console confirms `AWSLambda_FullAccess` and `AmazonS3FullAccess` are attached to the deployment user.
- Service name is `gab-payroll-api` to distinguish this project in the shared account; development stack name will be `gab-payroll-api-dev`.

## Current blocker

Read-only API probes returned:

| Action | Result |
|---|---|
| `cloudformation:ValidateTemplate` | `AccessDenied` |
| API Gateway V2 `GetApis` (`apigateway:GET /apis`) | `AccessDeniedException` |
| `iam:ListAttachedUserPolicies` / `iam:ListUserPolicies` | `AccessDenied` (console was used to verify attached policy names instead) |

The current deployment method uses CloudFormation to provision Lambda, API Gateway, CloudWatch logs, a deployment bucket and a Lambda execution role. Lambda access and S3 access alone do not grant the complete set of deployment permissions. No deployment has been attempted or successful live upload claimed at this stage.

## Message to ask the professor

> My IAM user `gab-payroll-deployer` has `AWSLambda_FullAccess` and `AmazonS3FullAccess`, and its credentials work. My PHP/Bref project uses the Serverless CLI. Read-only preflight checks in Sydney (`ap-southeast-2`) are denied for `cloudformation:ValidateTemplate` and API Gateway V2 `GetApis`. What deployment policy or existing deployment role should we use? The workflow also needs permission to create/pass the Lambda execution role and configure CloudWatch logs. Can you provide the approved permissions or the intended alternative deployment method for this lab?

Do not attach AdministratorAccess or change the professor's shared roles to work around these errors. A supplied execution role can avoid creating a new Lambda role, but does not by itself supply the caller's CloudFormation and API Gateway permissions.

## After approved access is available

Ask the coding assistant to recheck permissions and deploy. The configured command is:

```powershell
.\tools\deploy.ps1 -Bucket sam10-payroll-payslips-tabing -Region ap-southeast-2
```

Then test all four endpoints, verify/download the real PDF from S3, capture the six screenshots, complete the reflection and submit. No access keys need to be sent in chat.
