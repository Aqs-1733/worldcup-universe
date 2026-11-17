<#
WorldCup Universe - 停止本地网站（由 stop-local.bat 调用）。
  stop-local.bat            停止 PHP 网站和项目专用 MariaDB（数据保留在 storage\local-db，下次启动继续使用）
  stop-local.bat -KeepDb    只停止网站
只会结束本脚本启动过的进程（按 storage\runtime 下记录的 PID 并核对程序名），不会影响电脑上其他 PHP / 数据库。
#>
[CmdletBinding()]
param([switch]$KeepDb)
$ErrorActionPreference = 'Stop'
$Root = (Resolve-Path (Join-Path $PSScriptRoot '..\..')).Path
$Runtime = Join-Path $Root 'storage\runtime'
function Stop-Recorded([string]$pidFile, [string]$expect, [string]$label) {
  if (-not (Test-Path -LiteralPath $pidFile)) { Write-Host "    $label：没有运行记录"; return }
  $id = [int](Get-Content -LiteralPath $pidFile | Select-Object -First 1)
  $p = Get-Process -Id $id -ErrorAction SilentlyContinue
  if ($p -and $p.ProcessName -like $expect) { Stop-Process -Id $id -Force; Write-Host "    [OK] 已停止$label（PID $id）" -ForegroundColor Green }
  else { Write-Host "    $label：进程 $id 已不在运行" }
  Remove-Item -LiteralPath $pidFile -Force
}
Write-Host '==> 停止 WorldCup Universe 本地网站'
Stop-Recorded (Join-Path $Runtime 'local-server.pid') 'php*' '网站（PHP 内置服务器）'
if (-not $KeepDb) {
  $dbPid = Join-Path $Runtime 'local-mariadb.pid'; $binFile = Join-Path $Runtime 'local-mariadb.bin'
  if (Test-Path -LiteralPath $dbPid) {
    $id = [int](Get-Content -LiteralPath $dbPid | Select-Object -First 1)
    $p = Get-Process -Id $id -ErrorAction SilentlyContinue
    if ($p -and $p.ProcessName -like 'mysqld*') {
      # Clean shutdown first (flushes InnoDB); force only if it does not stop within 30 seconds.
      $port = (Select-String -LiteralPath (Join-Path $Root 'storage\local-db\my.ini') -Pattern '^port=(\d+)' | Select-Object -First 1).Matches.Groups[1].Value
      $admin = if (Test-Path -LiteralPath $binFile) { Join-Path (Get-Content -LiteralPath $binFile | Select-Object -First 1) 'mysqladmin.exe' } else { '' }
      if ($admin -and (Test-Path -LiteralPath $admin)) { & $admin -uroot --host=127.0.0.1 "--port=$port" shutdown 2>$null | Out-Null }
      if (-not $p.WaitForExit(30000)) { Stop-Process -Id $id -Force }
      Write-Host "    [OK] 已停止项目专用 MariaDB（PID $id，数据保留在 storage\local-db）" -ForegroundColor Green
    } else { Write-Host "    项目专用 MariaDB：进程 $id 已不在运行" }
    Remove-Item -LiteralPath $dbPid -Force
  } else { Write-Host '    项目专用 MariaDB：没有由本项目启动的实例（使用 XAMPP 等已有数据库时请在其控制面板中停止）' }
}
Write-Host '完成。再次启动请双击 deploy-local.bat。'
