param(
    [Parameter(Mandatory=$true)][ValidatePattern('^[a-z0-9][a-z0-9-]{1,61}[a-z0-9]$')][string]$Bucket,
    [string]$Region = 'ap-southeast-2',
    [string]$Profile = 'payroll-lab',
    [ValidatePattern('^[a-z0-9-]+$')][string]$Stage = 'dev'
)
$ErrorActionPreference = 'Stop'
Push-Location (Split-Path $PSScriptRoot -Parent)
try {
    $env:AWS_PROFILE = $Profile
    $env:AWS_REGION = $Region
    $env:PAYSLIP_BUCKET = $Bucket
    $env:AWS_EC2_METADATA_DISABLED = 'true'
    & php tools/check-aws.php
    if ($LASTEXITCODE -ne 0) { throw 'AWS preflight failed. Nothing was deployed.' }
    & php tests/run.php
    if ($LASTEXITCODE -ne 0) { throw 'Tests failed. Nothing was deployed.' }
    & .\node_modules\.bin\serverless.cmd deploy --region $Region --stage $Stage
    if ($LASTEXITCODE -ne 0) { throw 'Deployment failed. See the error above; a partially created stack may remain in CloudFormation.' }
    Write-Host 'Copy the printed HTTP API URL, then run tools/smoke-test.ps1 against it with -GeneratePdf.'
} finally { Pop-Location }
