param([int]$Interval=300)
# Native Yii2 publisher polling process, independent of HTTP requests.
$ErrorActionPreference='Stop'
$root=Split-Path -Parent $PSScriptRoot
$php=if($env:WCU_PHP){$env:WCU_PHP}else{'D:\XAMPP\php\php.exe'}
$runtime=Join-Path $root 'storage\runtime'
New-Item -ItemType Directory -Path $runtime -Force | Out-Null
$pidFile=Join-Path $runtime 'news-worker.pid'
if(Test-Path -LiteralPath $pidFile) {
    $old=Get-CimInstance Win32_Process -Filter "ProcessId = $(Get-Content -LiteralPath $pidFile)" -ErrorAction SilentlyContinue
    if($old -and $old.CommandLine -like '*yii*news/watch*') { Write-Output "News worker already running: $($old.ProcessId)";exit 0 }
}
$process=Start-Process -FilePath $php -ArgumentList @('yii','news/watch',([Math]::Max(60,$Interval)).ToString()) -WorkingDirectory $root -WindowStyle Hidden -PassThru -RedirectStandardOutput (Join-Path $runtime 'news-worker.log') -RedirectStandardError (Join-Path $runtime 'news-worker.err.log')
$process.Id | Set-Content -LiteralPath $pidFile
Write-Output "News worker started: $($process.Id); interval=$Interval seconds"
