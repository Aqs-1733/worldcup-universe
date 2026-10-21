param([string]$Php = '', [string]$Composer = '')
$ErrorActionPreference = 'Stop'
Set-Location -LiteralPath $PSScriptRoot
if (!$Php) { if (Test-Path -LiteralPath 'D:\XAMPP\php\php.exe') { $Php='D:\XAMPP\php\php.exe' } else { $Php=(Get-Command php -ErrorAction Stop).Source } }
$modules = & $Php -m
foreach ($module in @('pdo_mysql','mbstring','curl','SimpleXML','fileinfo')) { if ($modules -notcontains $module) { throw "Enable PHP extension: $module" } }
if (!(Test-Path -LiteralPath '.env')) {
    $content=Get-Content -Raw -LiteralPath '.env.example'
    $bytes=New-Object byte[] 32
    $rng=[System.Security.Cryptography.RandomNumberGenerator]::Create()
    $rng.GetBytes($bytes); $rng.Dispose()
    $secret=([BitConverter]::ToString($bytes)).Replace('-','').ToLowerInvariant()
    $content=$content -replace 'ADMIN_PASSWORD=.*','ADMIN_PASSWORD='
    $content += "`nYII_COOKIE_VALIDATION_KEY=$secret`n"
    Set-Content -LiteralPath '.env' -Value $content -Encoding utf8
    Write-Host 'Created .env. Configure MySQL and ADMIN_PASSWORD (12+ characters), then run setup.ps1 again.'
    exit 2
}
if (!(Test-Path -LiteralPath 'vendor\autoload.php')) {
    if ($Composer) { & $Php $Composer install --no-dev --prefer-dist --no-interaction }
    else { & composer install --no-dev --prefer-dist --no-interaction }
    if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
}
& $Php yii app/install
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
Write-Host 'Yii2 backend ready. Run start_php.bat or: php -S 127.0.0.1:8080 -t public public/index.php'
