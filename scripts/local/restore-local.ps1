<#
WorldCup Universe - 在 Windows 本地恢复生产备份（由 restore-local.bat 调用；备份先用 fetch-backup.bat 下载解密）。

  restore-local.bat -Snapshot 20261118-042355                  验证模式（默认）：导入到独立的 worldcup_universe_restorecheck
                                                               数据库，与备份时记录的每张表行数逐一比对；本地网站数据不变。
  restore-local.bat -Snapshot 20261118-042355 -IntoLocalSite   用生产数据替换本地网站的数据库和上传文件（先自动备份本地数据，
                                                               需要输入 YES 确认；本地 .env 保持不变）。

两种“初始化”的区别：deploy-local.bat 用随源码提交的公开数据（球队、球员、赛程、新闻样例）初始化一个新网站；
本脚本恢复的是生产服务器上的真实数据（用户、论坛、后台上传的文件和课程作业）。
#>
[CmdletBinding()]
param(
  [string]$Snapshot = '',
  [switch]$IntoLocalSite,
  [switch]$Yes,
  [string]$CheckDatabase = 'worldcup_universe_restorecheck'
)
$ErrorActionPreference = 'Stop'
$Root = (Resolve-Path (Join-Path $PSScriptRoot '..\..')).Path
$Runtime = Join-Path $Root 'storage\runtime'
function Fail([string]$m, [string]$hint = '') { Write-Host ''; Write-Host "[失败] $m" -ForegroundColor Red; if ($hint) { Write-Host $hint -ForegroundColor Yellow }; exit 1 }
function Ok([string]$m) { Write-Host "    [OK] $m" -ForegroundColor Green }
function Read-EnvFile([string]$path) { $m = @{}; foreach ($l in [IO.File]::ReadAllLines($path)) { if ($l -match '^\s*([A-Z0-9_]+)\s*=(.*)$') { $m[$Matches[1]] = $Matches[2].Trim().Trim('"').Trim("'") } }; return $m }
function Gunzip([string]$src, [string]$dst) {
  $in = [IO.File]::OpenRead($src); $gz = New-Object IO.Compression.GZipStream($in, [IO.Compression.CompressionMode]::Decompress); $out = [IO.File]::Create($dst)
  try { $gz.CopyTo($out) } finally { $out.Dispose(); $gz.Dispose(); $in.Dispose() }
}

# --- locate snapshot, local database and client -----------------------------------------------------------------
$backups = Join-Path $Root 'backups'
if (-not $Snapshot) { $Snapshot = (Get-ChildItem -LiteralPath $backups -Directory -ErrorAction SilentlyContinue | Where-Object { $_.Name -match '^\d{8}-\d{6}$' } | Sort-Object Name -Descending | Select-Object -First 1).Name }
$dir = Join-Path $backups $Snapshot
if (-not $Snapshot -or -not (Test-Path -LiteralPath (Join-Path $dir 'summary.json'))) { Fail "没有找到已解密的快照 backups\$Snapshot。" '请先运行 fetch-backup.bat 下载并解密备份。' }
$summary = Get-Content -LiteralPath (Join-Path $dir 'summary.json') -Raw -Encoding UTF8 | ConvertFrom-Json
$dbGz = Get-ChildItem -LiteralPath $dir -Filter 'db-*.sql.gz' | Select-Object -First 1
$filesTar = Get-ChildItem -LiteralPath $dir -Filter 'files-*.tar.gz' | Select-Object -First 1
if (-not $dbGz -or -not $filesTar) { Fail "快照目录不完整：$dir" }
$EnvFile = Join-Path $Root '.env'
if (-not (Test-Path -LiteralPath $EnvFile)) { Fail '本地网站还没有部署。' '请先运行 deploy-local.bat。' }
$cfg = Read-EnvFile $EnvFile
$binFile = Join-Path $Runtime 'local-mariadb.bin'
if ($cfg['WCU_LOCAL_DB'] -ne 'private' -or -not (Test-Path -LiteralPath $binFile)) { Fail '本脚本使用 deploy-local.bat 创建的项目专用 MariaDB。' '如果你连接的是自己的数据库服务器，请参照 docs/deployment.md 手动导入。' }
$Bin = (Get-Content -LiteralPath $binFile | Select-Object -First 1)
$mysql = Join-Path $Bin 'mysql.exe'; $dump = Join-Path $Bin 'mysqldump.exe'
$port = $cfg['DB_PORT']
$probe = New-Object Net.Sockets.TcpClient; try { $probe.Connect('127.0.0.1', [int]$port) } catch { } ; $up = $probe.Connected; $probe.Close()
if (-not $up) { Fail "本地 MariaDB（端口 $port）没有运行。" '请先运行 deploy-local.bat 启动本地网站和数据库。' }
$rootArgs = @('-uroot', '--host=127.0.0.1', "--port=$port", '--default-character-set=utf8mb4')
function Sql([string]$db, [string]$q) { $a = $rootArgs + @('-N', '-B'); if ($db) { $a += $db }; $r = & $mysql @a -e $q; if ($LASTEXITCODE -ne 0) { Fail "SQL 执行失败：$q" }; return $r }
function Import([string]$db, [string]$sqlFile) {
  # cmd's < redirection streams the file byte for byte (PowerShell 5.1 pipes would re-encode it).
  $line = '"' + $mysql + '" ' + ($rootArgs -join ' ') + ' ' + $db + ' < "' + $sqlFile + '"'
  & cmd.exe /d /c $line
  if ($LASTEXITCODE -ne 0) { Fail "导入数据库 $db 失败（mysql 退出码 $LASTEXITCODE）。" }
}
function Compare-Counts([string]$db) {
  $bad = @(); $n = 0
  foreach ($p in $summary.row_counts.PSObject.Properties) {
    $got = [int](Sql $db "SELECT COUNT(*) FROM ``$($p.Name)``"); $n++
    if ($got -ne [int]$p.Value) { $bad += "$($p.Name): 备份时 $($p.Value)，恢复后 $got" }
  }
  return @{ checked = $n; bad = $bad }
}
$tmp = Join-Path $env:TEMP "wcu-restore-$Snapshot"; New-Item -ItemType Directory -Force -Path $tmp | Out-Null
$sql = Join-Path $tmp 'db.sql'
Write-Host "==> 快照 $Snapshot（生产时间 $($summary.created)，应用版本 $($summary.app_commit)）"
Gunzip $dbGz.FullName $sql
Ok "数据库备份已解压（$([math]::Round((Get-Item -LiteralPath $sql).Length / 1MB, 1)) MB）"

if (-not $IntoLocalSite) {
  # ---- verification mode -------------------------------------------------------------------------------------------
  if ($CheckDatabase -notmatch '^[A-Za-z0-9_]+$' -or $CheckDatabase -eq $cfg['DB_DATABASE']) { Fail '验证用的数据库名无效，或与本地网站的数据库同名。' }
  Write-Host "==> 导入到独立的验证数据库 $CheckDatabase（每次验证都会重建它；本地网站数据库 $($cfg['DB_DATABASE']) 不受影响）"
  Sql '' "DROP DATABASE IF EXISTS ``$CheckDatabase``; CREATE DATABASE ``$CheckDatabase`` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci" | Out-Null
  Import $CheckDatabase $sql
  $r = Compare-Counts $CheckDatabase
  Write-Host '==> 检查上传文件归档'
  $listing = & "$env:SystemRoot\System32\tar.exe" -tzf $filesTar.FullName
  $uploads = @($listing | Where-Object { $_ -match '^(public/uploads/|data/(team|personal)/uploads/).+[^/]$' -and $_ -notmatch '\.gitkeep$' }).Count
  $hasEnv = [bool]($listing -contains '.env')
  Remove-Item -LiteralPath $tmp -Recurse -Force
  Write-Host ''
  Write-Host "  数据表：$($r.checked) 张，行数不一致：$($r.bad.Count)"
  $r.bad | ForEach-Object { Write-Host "    $_" -ForegroundColor Red }
  Write-Host "  上传文件：归档中 $uploads 个，备份时记录 $($summary.uploaded_files) 个；.env：$(if ($hasEnv) { '包含' } else { '缺失' })"
  if ($r.bad.Count -or $uploads -ne [int]$summary.uploaded_files -or -not $hasEnv) { Fail '恢复验证未通过。' }
  Write-Host ''
  Write-Host "[通过] 快照 $Snapshot 可以完整恢复：$($r.checked) 张表的行数与备份时一致，$uploads 个上传文件齐全。" -ForegroundColor Green
  Write-Host "      验证数据库 $CheckDatabase 保留在本地 MariaDB 中，可用 phpMyAdmin / mysql 查看；不需要时可删除。"
  exit 0
}

# ---- replace the local site's data -------------------------------------------------------------------------------
$db = $cfg['DB_DATABASE']
Write-Host ''
Write-Host "将用生产数据替换本地网站的数据库 $db 和上传文件（public\uploads、data\team、data\personal、storage\uploads）。" -ForegroundColor Yellow
Write-Host '本地 .env 保持不变；恢复后管理员和用户的密码与生产环境相同。' -ForegroundColor Yellow
if (-not $Yes) { $ans = Read-Host '确认请输入 YES'; if ($ans -cne 'YES') { Remove-Item -LiteralPath $tmp -Recurse -Force; Write-Host '已取消，没有改动任何数据。'; exit 3 } }
& (Join-Path $Root 'scripts\local\stop-local.ps1') -KeepDb | Out-Null    # no request may write while data is swapped
$stamp = Get-Date -Format 'yyyyMMdd-HHmmss'; $pre = Join-Path $backups "pre-restore-$stamp"; New-Item -ItemType Directory -Force -Path $pre | Out-Null
Write-Host "==> 先备份本地现有数据到 $pre"
$line = '"' + $dump + '" ' + ($rootArgs -join ' ') + ' --single-transaction --routines --triggers ' + $db + ' > "' + (Join-Path $pre 'db.sql') + '"'
& cmd.exe /d /c $line; if ($LASTEXITCODE -ne 0) { Fail '本地数据库备份失败，已停止，没有改动任何数据。' }
& "$env:SystemRoot\System32\tar.exe" -czf (Join-Path $pre 'files.tar.gz') -C $Root public/uploads data/team data/personal storage/uploads
if ($LASTEXITCODE -ne 0) { Fail '本地文件备份失败，已停止，没有改动任何数据。' }
Ok '本地数据已备份'
Write-Host '==> 导入生产数据库'
Import $db $sql
Write-Host '==> 解包上传文件（不包含 .env）'
& "$env:SystemRoot\System32\tar.exe" -xzf $filesTar.FullName -C $Root --exclude .env public/uploads data/team data/personal storage/uploads
if ($LASTEXITCODE -ne 0) { Fail "解包失败。可用 $pre 中的备份恢复本地原状。" }
Remove-Item -LiteralPath $tmp -Recurse -Force
$r = Compare-Counts $db
if ($r.bad.Count) { Write-Host '行数差异（恢复后又迁移过的表属正常）：'; $r.bad | ForEach-Object { Write-Host "    $_" } }
Ok "生产数据已导入（$($r.checked) 张表）"
Write-Host '==> 迁移、清缓存并重新启动网站'
& (Join-Path $Root 'scripts\local\deploy-local.ps1') -NoBrowser
if ($LASTEXITCODE -ne 0) { Fail "网站启动失败。本地原数据在 $pre（db.sql 与 files.tar.gz）。" }
Write-Host "[完成] 本地网站现在使用生产快照 $Snapshot 的数据。恢复前的本地数据保存在 $pre。" -ForegroundColor Green
exit 0
