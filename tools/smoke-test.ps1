param(
    [Parameter(Mandatory=$true)][string]$BaseUrl,
    [ValidateRange(1,2147483647)][int]$PayrollId = 1,
    [switch]$GeneratePdf
)
$ErrorActionPreference = 'Stop'
$BaseUrl = $BaseUrl.TrimEnd('/')
$uri = [uri]$BaseUrl
if ($uri.Scheme -ne 'https' -and -not ($uri.Scheme -eq 'http' -and $uri.IsLoopback)) {
    throw 'Use HTTPS for a deployed API, or HTTP on localhost for a local test.'
}
$root = Split-Path $PSScriptRoot -Parent
$outputDirectory = Join-Path $root 'output/evidence'
New-Item -ItemType Directory -Force -Path $outputDirectory | Out-Null
$requests = @(
    @{Method='GET';Path='/api/employees';File='employees.json'},
    @{Method='GET';Path='/api/reports/payroll';File='payroll-report.json'},
    @{Method='GET';Path="/api/payslips/$PayrollId";File='payslip.json'}
)
if ($GeneratePdf) { $requests += @{Method='POST';Path="/api/payslips/$PayrollId/generate";File='generate.json'} }
foreach ($request in $requests) {
    $url = $BaseUrl + $request.Path
    $response = Invoke-WebRequest -UseBasicParsing -Uri $url -Method $request.Method
    $payload = $response.Content | ConvertFrom-Json
    if ($response.StatusCode -ne 200 -or $payload.success -ne $true) { throw "Request failed: $url" }
    if ($request.File -eq 'employees.json' -and @($payload.data).Count -ne 500) { throw 'Employee count does not match the bundled dataset.' }
    if ($request.File -eq 'payroll-report.json') {
        if ($payload.data.total_payroll_records -ne 600 -or
            [decimal]$payload.data.total_gross_pay -ne [decimal]'4981004.63' -or
            [decimal]$payload.data.total_deductions -ne [decimal]'849478.70' -or
            [decimal]$payload.data.total_net_pay -ne [decimal]'4131525.93') { throw 'Payroll totals do not match the bundled dataset.' }
    }
    if ($request.File -eq 'payslip.json' -and $payload.data.payroll_id -ne $PayrollId) { throw 'Incorrect payroll record returned.' }
    if ($request.File -eq 'generate.json' -and $payload.data.payslip_key -ne "payslips/$PayrollId.pdf") { throw 'Incorrect S3 key returned.' }
    $payload | ConvertTo-Json -Depth 10 | Set-Content -LiteralPath (Join-Path $outputDirectory $request.File) -Encoding UTF8
    Write-Host "$($request.Method) $url -> HTTP $($response.StatusCode), success=true"
}
Write-Host "Response files saved to $outputDirectory. These are test results, not screenshots."
