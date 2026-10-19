# Serverless Payroll Management API Using PHP and AWS Lambda

Repository URL: https://github.com/gab26g/serverless-payroll-api

Deployed API base URL: https://51fjkdjyef.execute-api.ap-southeast-2.amazonaws.com

Deployment region: Asia Pacific (Sydney), `ap-southeast-2`.

## Successful endpoint tests

| Method | Endpoint | Verified result |
|---|---|---|
| GET | `/api/employees` | HTTP 200, success=true; 500 employee records |
| GET | `/api/reports/payroll` | HTTP 200; 600 payroll records and actual CSV totals |
| GET | `/api/payslips/1` | HTTP 200; employee #319, Deniz Fischer, joined to payroll #1 |
| POST | `/api/payslips/1/generate` | HTTP 200; readable PDF generated and uploaded to S3 |

Total gross pay: **4,981,004.63**. Total deductions: **849,478.70**. Total net pay: **4,131,525.93**. Deductions are derived as gross minus net, and calculations use integer cents.

S3 bucket: `sam10-payroll-payslips-tabing`. Generated key: `payslips/1.pdf`. The object is private, uses SSE-S3 encryption and has content type `application/pdf`. It was downloaded through authenticated AWS access and visually verified. An unsigned object URL is not intended as a public download link.

## Screenshots and PDF

The coding assistant saved the following real evidence in the local project's `output/evidence/` folder; they are included in the submission packet ZIP:

1. `01-employees.jpg` - live employee JSON response, first records visible.
2. `02-payroll-report.jpg` - live payroll summary JSON.
3. `03-payslip.jpg` - live individual payslip JSON.
4. `04-generate.jpg` - CloudShell POST command, HTTP 200 and successful upload response.
5. `05-s3-object.jpg` - S3 console object overview showing bucket, region, key and PDF size.
6. `06-pdf.png` - rendered page of the actual PDF downloaded from S3.

Extra evidence: `00-deployment.jpg` shows the successful Serverless deployment. The downloaded PDF is included as `payslip-1-live.pdf`.

The GET screenshots use the browser's built-in pretty-print display; their endpoint URLs are specified in the table above. No response content was edited. The employees viewport shows the beginning of the complete 500-record response. Full response JSON files are also included.

## Issue encountered and how it was resolved

The shared IAM user's access-key limit had already been reached, so a separate deployment user was created. Its credentials worked, but the attached `AWSLambda_FullAccess` and `AmazonS3FullAccess` policies did not grant all the permissions required by the Serverless deployment workflow. Read-only preflight checks returned access-denied errors for CloudFormation and API Gateway. Deployment was completed through AWS CloudShell using the existing shared console login, which already had deployment permissions. The PHP package was built locally and uploaded as a portable bundle because CloudShell did not have PHP or Composer. The CloudFormation template validated, the stack deployed successfully, all four live endpoints returned HTTP 200, and the uploaded PDF was verified in S3. No shared keys were deleted and no extra policies were attached to the student user.

## Reflection draft - review and personalize before submitting

This activity helped me understand how a PHP application can provide JSON endpoints through AWS Lambda and API Gateway using Bref. I learned how employee and payroll CSV records are joined through their IDs, and why calculating money in integer cents avoids rounding errors. I also learned how Dompdf creates a readable payslip and how the AWS SDK stores it in a private S3 bucket. The deployment issue showed me that valid credentials do not automatically include all the permissions a deployment tool needs. I can now distinguish the identity that deploys an application from the Lambda execution role that controls what the running application can access.

Before submitting, edit the reflection to match your own understanding and add any personal difficulty or learning you want to mention.
