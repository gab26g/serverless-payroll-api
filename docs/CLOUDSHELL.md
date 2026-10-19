# Deploy from the existing shared console session

Use this route when the new student's access keys lack provisioning permissions but the already-authorized shared console login can deploy. CloudShell automatically uses the signed-in console identity. No shared access key needs to be copied or created, and no extra policies need to be attached to the student user.

## Prepared artifact

The coding assistant builds the PHP application locally with Composer and OSS Serverless, then runs:

```powershell
$env:PAYSLIP_BUCKET = 'sam10-payroll-payslips-tabing'
npm.cmd run package -- --region ap-southeast-2
python tools/prepare-cloudshell.py
```

Use an available Python 3 interpreter if `python` is not on PATH. The helper validates the package's service, region and bucket, converts Windows artifact paths to portable paths, and creates `output/gab-payroll-cloudshell-01a15376.zip`. This contains the already-built Lambda ZIP, Serverless state/templates, Composer metadata, Node deployment manifests and the Bref deployment plugin. It excludes local credentials and Node dependencies.

CloudShell does not need PHP or Composer for this prepackaged deployment. Do not repackage the minimal outer bundle; it is intended for `serverless deploy --package .serverless`.

## CloudShell preparation

1. Stay signed in using the existing shared console login, choose **Sydney**, then click **CloudShell**.
2. Open CloudShell full-page if the bottom panel is too small.
3. Choose **Actions > Upload file**, then select the generated ZIP from the local project's `output` folder.
4. Wait until the upload success notification appears.
5. Verify the SHA256 shown by the local builder against `sha256sum gab-payroll-cloudshell-01a15376.zip` in CloudShell.
6. Extract without overwriting existing files and enter this project's uniquely named directory:

```bash
unzip -n -q gab-payroll-cloudshell-01a15376.zip
cd gab-payroll-cloudshell-01a15376
export PAYSLIP_BUCKET=sam10-payroll-payslips-tabing
export AWS_REGION=ap-southeast-2
npm ci --no-audit --no-fund
```

Check the account and verify that the project stack does not already belong to another exercise. If the stack exists unexpectedly, stop and inspect it before proceeding:

```bash
aws sts get-caller-identity --query '{Account:Account,Arn:Arn}' --output json --no-cli-pager
aws cloudformation describe-stacks --stack-name gab-payroll-api-dev --region ap-southeast-2 --query 'Stacks[0].StackStatus' --output text --no-cli-pager
```

For an initial deployment, the expected second result is a message that the stack does not exist. Validate the prepared template without creating resources:

```bash
aws cloudformation validate-template --template-body file://.serverless/cloudformation-template-update-stack.json --region ap-southeast-2 --query '{Description:Description,Capabilities:Capabilities}' --output json --no-cli-pager
```

## Deployment action

The command below creates the `gab-payroll-api-dev` CloudFormation stack, a deployment bucket, Lambda function, public HTTP API, logs and Lambda execution role. The application role can write logs and upload PDFs only to `arn:aws:s3:::sam10-payroll-payslips-tabing/payslips/*`. It does not change the student user's policies or the existing bucket's public-access settings.

When the user has confirmed this concrete resource creation, run:

```bash
./node_modules/.bin/serverless deploy --package .serverless --region ap-southeast-2 --stage dev
```

Copy the API endpoint printed on success. Test all four routes and verify `payslips/1.pdf` in S3. The local source stays in GitHub; the CloudShell bundle is an ignored build artifact.
