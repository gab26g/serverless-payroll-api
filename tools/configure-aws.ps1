param([string]$Profile = 'payroll-lab')
$ErrorActionPreference = 'Stop'
if ($Profile -notmatch '^[a-zA-Z0-9_-]+$') { throw 'Use letters, numbers, hyphens or underscores for the profile.' }
$awsDirectory = Join-Path ([Environment]::GetFolderPath('UserProfile')) '.aws'
$credentialPath = Join-Path $awsDirectory 'credentials'
if ((Test-Path -LiteralPath $credentialPath) -and ((Get-Content -LiteralPath $credentialPath -Raw) -match "(?m)^\[$([regex]::Escape($Profile))\]\s*$")) {
    throw "Profile [$Profile] already exists. Keep it, or choose a different -Profile name. Existing credentials were not modified."
}
function Read-PrivateValue([string]$Prompt) {
    $secure = Read-Host $Prompt -AsSecureString
    $pointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($secure)
    try { return [Runtime.InteropServices.Marshal]::PtrToStringBSTR($pointer) }
    finally { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($pointer) }
}
$accessId = Read-PrivateValue 'AWS access key ID (hidden)'
$accessSecret = Read-PrivateValue 'AWS secret access key (hidden)'
$sessionToken = Read-PrivateValue 'Session token for temporary credentials (press Enter if none)'
try {
    if ([string]::IsNullOrWhiteSpace($accessId) -or [string]::IsNullOrWhiteSpace($accessSecret)) { throw 'Access key ID and secret are required.' }
    New-Item -ItemType Directory -Force -Path $awsDirectory | Out-Null
    $text = "`n[$Profile]`naws_access_key_id = $accessId`naws_secret_access_key = $accessSecret`n"
    if ($sessionToken) { $text += "aws_session_token = $sessionToken`n" }
    [IO.File]::AppendAllText($credentialPath, $text, [Text.UTF8Encoding]::new($false))
    Write-Host "Saved profile [$Profile] outside the repository. Credential values were not printed."
} finally {
    $accessId = $null; $accessSecret = $null; $sessionToken = $null; $text = $null
}
