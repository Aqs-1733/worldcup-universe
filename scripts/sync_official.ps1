param([string]$Python='python', [string]$Php='php')
$ErrorActionPreference='Stop'
Set-Location -LiteralPath (Split-Path -Parent $PSScriptRoot)
# Fetcher prints the completed snapshot directory last; partial runs are never imported.
$output=& $Python scripts/fetch_fifa.py
$code=$LASTEXITCODE
$output | ForEach-Object { Write-Host $_ }
if($code -ne 0) {exit $code}
$folder=($output | Select-Object -Last 1).Trim()
if(!(Test-Path -LiteralPath (Join-Path $folder 'manifest.json'))) {throw 'Completed manifest missing.'}
& $Php yii fifa/import $folder
exit $LASTEXITCODE
