<#
WorldCup Universe - Windows 一键本地部署与启动（由项目根目录的 deploy-local.bat 调用）。

  deploy-local.bat                         默认：项目专用 MariaDB（端口 3317）+ 网站 http://127.0.0.1:8080
  deploy-local.bat -Port 8090              换网站端口
  deploy-local.bat -UseExistingDb -DbPort 3306   改用电脑上已运行的 MariaDB（需要它的 root 账号建库）
  deploy-local.bat -Php D:\php82\php.exe   指定 PHP；-MariaDbBin C:\xampp\mysql\bin 指定 MariaDB 程序目录

每一步都可以重复运行：已有的 .env、数据库、管理员密码和上传文件都不会被覆盖或重置。
任何一步失败都会显示原因并以非 0 退出码结束，不会提示“部署成功”。
#>
[CmdletBinding()]
param(
  [int]$Port = 8080,
  [string]$Php = '',
  [string]$MariaDbBin = '',
  [switch]$UseExistingDb,
  [string]$DbHost = '127.0.0.1',
  [int]$DbPort = 0,
  [string]$Database = '',
  [switch]$NoBrowser
)
$ErrorActionPreference = 'Stop'
$Root = (Resolve-Path (Join-Path $PSScriptRoot '..\..')).Path
Set-Location -LiteralPath $Root
$RuntimeDir = Join-Path $Root 'storage\runtime'
$LocalDbDir = Join-Path $Root 'storage\local-db'
$LogDir     = Join-Path $Root 'storage\logs'
$PrivatePort = 3317

function Step([string]$m) { Write-Host ''; Write-Host "==> $m" -ForegroundColor Cyan }
function Ok([string]$m)   { Write-Host "    [OK] $m" -ForegroundColor Green }
function Info([string]$m) { Write-Host "    $m" }
function Fail([string]$m, [string]$hint = '') {
  Write-Host ''; Write-Host "[失败] $m" -ForegroundColor Red
  if ($hint) { Write-Host ''; Write-Host $hint -ForegroundColor Yellow }
  exit 1
}
function New-Secret([int]$bytes) {
  $b = New-Object byte[] $bytes; $r = [System.Security.Cryptography.RandomNumberGenerator]::Create(); $r.GetBytes($b); $r.Dispose()
  return ([BitConverter]::ToString($b)).Replace('-', '').ToLowerInvariant()
}
function New-Password {
  $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789'.ToCharArray()
  $b = New-Object byte[] 18; $r = [System.Security.Cryptography.RandomNumberGenerator]::Create(); $r.GetBytes($b); $r.Dispose()
  return -join ($b | ForEach-Object { $chars[$_ % $chars.Length] })
}
function Test-Port([int]$p) {
  $c = New-Object System.Net.Sockets.TcpClient
  try { $a = $c.BeginConnect('127.0.0.1', $p, $null, $null); $ok = $a.AsyncWaitHandle.WaitOne(400) -and $c.Connected; return $ok } catch { return $false } finally { $c.Close() }
}
function Get-PortOwner([int]$p) {
  try {
    $c = Get-NetTCPConnection -LocalPort $p -State Listen -ErrorAction Stop | Select-Object -First 1
    $proc = Get-Process -Id $c.OwningProcess -ErrorAction SilentlyContinue
    if ($proc) { return "$($proc.ProcessName) (PID $($c.OwningProcess))" } else { return "PID $($c.OwningProcess)" }
  } catch { return '未知进程' }
}
function Quote([string]$a) { if ($a -match '[\s"]') { return '"' + ($a -replace '"', '\"') + '"' } else { return $a } }
# ShellExecute with a hidden window gives the process its own console, so closing this deployment window keeps the
# site and database running. (Win32_Process.Create was tried first: WMI's provider host ends those processes.)
function Start-Detached([string]$exe, [string[]]$argv, [string]$cwd) {
  $line = ($argv | ForEach-Object { Quote $_ }) -join ' '
  $p = Start-Process -FilePath $exe -ArgumentList $line -WorkingDirectory $cwd -WindowStyle Hidden -PassThru
  return [int]$p.Id
}
function Get-Status([string]$url) {
  $req = [System.Net.HttpWebRequest]::Create($url); $req.AllowAutoRedirect = $false; $req.Proxy = $null; $req.Timeout = 30000
  try { $res = $req.GetResponse(); $code = [int]$res.StatusCode; $res.Close(); return $code }
  catch [System.Net.WebException] { if ($_.Exception.Response) { $code = [int]$_.Exception.Response.StatusCode; $_.Exception.Response.Close(); return $code } return 0 }
}
function Read-EnvFile([string]$path) {
  $map = @{}
  foreach ($line in [System.IO.File]::ReadAllLines($path)) {
    if ($line -match '^\s*([A-Z0-9_]+)\s*=(.*)$') { $map[$Matches[1]] = $Matches[2].Trim().Trim('"').Trim("'") }
  }
  return $map
}
function Set-EnvValues([string]$path, [hashtable]$values) {
  $lines = New-Object System.Collections.Generic.List[string]
  $lines.AddRange([string[]][System.IO.File]::ReadAllLines($path))
  foreach ($k in $values.Keys) {
    $done = $false
    for ($i = 0; $i -lt $lines.Count; $i++) { if ($lines[$i] -match "^\s*$k\s*=") { $lines[$i] = "$k=$($values[$k])"; $done = $true } }
    if (-not $done) { $lines.Add("$k=$($values[$k])") }
  }
  [System.IO.File]::WriteAllLines($path, $lines, (New-Object System.Text.UTF8Encoding($false)))   # no BOM: PHP reads it
}

Write-Host '============================================================'
Write-Host ' WorldCup Universe 本地一键部署（PHP + Yii2 + MariaDB）'
Write-Host " 项目目录：$Root"
Write-Host '============================================================'
if ($Root -match '[^\x00-\x7F]') { Info '提示：项目路径含中文或特殊字符；如数据库启动失败，请把项目放到纯英文路径（例如 D:\worldcup-universe）。' }

# ---------------------------------------------------------------------------------------------------------------
Step '1/7 检查 PHP（需要 8.1 及以上，扩展 pdo_mysql / mbstring / curl / fileinfo / openssl）'
$candidates = New-Object System.Collections.Generic.List[string]
if ($Php) { $candidates.Add($Php) }
if ($env:WCU_PHP) { $candidates.Add($env:WCU_PHP) }
Get-ChildItem -LiteralPath $Root -Directory -Filter 'php*' -ErrorAction SilentlyContinue | ForEach-Object { $candidates.Add((Join-Path $_.FullName 'php.exe')) }
foreach ($x in @('C:\xampp\php\php.exe', 'D:\xampp\php\php.exe', 'E:\xampp\php\php.exe', 'C:\php\php.exe')) { $candidates.Add($x) }
$onPath = Get-Command php.exe -ErrorAction SilentlyContinue; if ($onPath) { $candidates.Add($onPath.Source) }
$PhpExe = $null; $seen = @()
foreach ($c in $candidates) {
  if (-not $c -or -not (Test-Path -LiteralPath $c)) { continue }
  $v = (& $c -r 'echo PHP_VERSION;' 2>$null)
  if (-not $v) { continue }
  $seen += "$c ($v)"
  if ([version]($v -replace '[^0-9.].*$', '') -ge [version]'8.1') { $PhpExe = (Resolve-Path -LiteralPath $c).Path; $PhpVersion = $v; break }
}
if (-not $PhpExe) {
  $found = if ($seen) { "找到但版本过低：`n      " + ($seen -join "`n      ") } else { '没有找到 php.exe。' }
  Fail "没有可用的 PHP 8.1+。$found" @"
解决方法（任选其一）：
  1. 安装 XAMPP 8.2.x（自带 PHP 8.2 和 MariaDB 10.4）：https://www.apachefriends.org/
  2. 从 https://windows.php.net/download/ 下载 PHP 8.2/8.3 x64 Thread Safe 的 zip，解压后运行：
       deploy-local.bat -Php D:\php82\php.exe
"@
}
# Extensions that are not enabled in php.ini but ship as DLLs are turned on for this project only with -d flags.
$PhpDir = Split-Path $PhpExe -Parent
$loaded = (& $PhpExe -m) | ForEach-Object { $_.Trim().ToLowerInvariant() }
$PhpArgs = New-Object System.Collections.Generic.List[string]
$extDir = (& $PhpExe -r "echo ini_get('extension_dir');")   # PHP code uses single quotes: Windows PowerShell 5.1 strips inner double quotes
if (-not (Test-Path -LiteralPath (Join-Path $extDir 'php_mbstring.dll'))) { $extDir = Join-Path $PhpDir 'ext'; $PhpArgs.Add('-d'); $PhpArgs.Add("extension_dir=$extDir") }
$missing = @()
foreach ($ext in @('pdo_mysql', 'mbstring', 'curl', 'fileinfo', 'openssl', 'simplexml', 'zip')) {
  if ($loaded -contains $ext) { continue }
  if (Test-Path -LiteralPath (Join-Path $extDir "php_$ext.dll")) { $PhpArgs.Add('-d'); $PhpArgs.Add("extension=$ext") }
  elseif ($ext -ne 'zip') { $missing += $ext }
}
if ($missing) {
  Fail "PHP 缺少扩展：$($missing -join ', ')（$PhpExe）" "请在 $PhpDir\php.ini 中启用对应的 extension=... 行，或换用 XAMPP 8.2 自带的 PHP。"
}
$check = (& $PhpExe @PhpArgs -r "foreach(['pdo_mysql','mbstring','curl','fileinfo','openssl'] as `$e){if(!extension_loaded(`$e)){echo `$e,' ';}}")
if ($check) { Fail "PHP 扩展无法加载：$check" "检查 $extDir 中是否有对应的 php_*.dll，以及它们与 php.exe 的版本是否一致。" }
$PhpArgs = [string[]]$PhpArgs.ToArray()
Ok "PHP $PhpVersion  $PhpExe"
if ($PhpArgs.Count) { Info "本项目临时启用扩展：$(($PhpArgs | Where-Object { $_ -like 'extension=*' }) -join ', ')" }

# ---------------------------------------------------------------------------------------------------------------
Step '2/7 检查 Yii2 依赖（vendor 目录）'
if (Test-Path -LiteralPath (Join-Path $Root 'vendor\yiisoft\yii2\Yii.php')) { Ok 'vendor 已存在（随源码提交），无需 Composer' }
else {
  $phar = $null
  foreach ($p in @((Join-Path $Root 'composer.phar'), (Join-Path $Root 'tools\composer.phar'))) { if (Test-Path -LiteralPath $p) { $phar = $p; break } }
  if (-not $phar) { $cmd = Get-Command composer -ErrorAction SilentlyContinue; if ($cmd) { $p = Join-Path (Split-Path $cmd.Source -Parent) 'composer.phar'; if (Test-Path -LiteralPath $p) { $phar = $p } } }
  if (-not $phar) {
    Fail 'vendor 目录缺失，且没有找到 Composer。' @"
解决方法：
  1. 使用随作业提交的完整源码（其中包含 vendor 目录）；或
  2. 安装 Composer（https://getcomposer.org/Composer-Setup.exe），或把 composer.phar 放到项目根目录，再重新运行 deploy-local.bat。
"@
  }
  Info "使用 $phar 安装依赖（需要联网）……"
  & $PhpExe @PhpArgs $phar install --no-dev --prefer-dist --no-interaction --optimize-autoloader
  if ($LASTEXITCODE -ne 0) { Fail "composer install 失败（退出码 $LASTEXITCODE）。" '请检查网络，或使用包含 vendor 目录的完整源码。' }
  Ok 'Composer 依赖已安装'
}

# ---------------------------------------------------------------------------------------------------------------
Step '3/7 本地配置 .env（首次运行自动生成，之后保持不变）'
$EnvFile = Join-Path $Root '.env'
$FirstEnv = $false
if (-not (Test-Path -LiteralPath $EnvFile)) {
  if (-not (Test-Path -LiteralPath (Join-Path $Root '.env.example'))) { Fail '.env.example 不存在，源码不完整。' }
  Copy-Item -LiteralPath (Join-Path $Root '.env.example') -Destination $EnvFile
  $dbName = if ($Database) { $Database } else { 'worldcup_universe_local' }
  $isPrivate = -not $UseExistingDb
  $values = @{
    APP_ENV = 'development'; APP_URL = "http://127.0.0.1:$Port"
    DB_HOST = $(if ($isPrivate) { '127.0.0.1' } else { $DbHost })
    DB_PORT = $(if ($isPrivate) { "$PrivatePort" } elseif ($DbPort) { "$DbPort" } else { '3306' })
    DB_DATABASE = $dbName; DB_USERNAME = $dbName; DB_PASSWORD = (New-Secret 20)
    ADMIN_USERNAME = 'admin'; ADMIN_PASSWORD = (New-Password)
    YII_COOKIE_VALIDATION_KEY = (New-Secret 32)
    WCU_LOCAL_DB = $(if ($isPrivate) { 'private' } else { 'external' })
  }
  Set-EnvValues $EnvFile $values
  $FirstEnv = $true
  Ok '已生成 .env：随机数据库密码、管理员密码和 Yii2 Cookie 密钥（.env 不会提交到 Git）'
} else {
  $cur = Read-EnvFile $EnvFile
  if (-not $cur['YII_COOKIE_VALIDATION_KEY'] -or $cur['YII_COOKIE_VALIDATION_KEY'].Length -lt 32) { Set-EnvValues $EnvFile @{ YII_COOKIE_VALIDATION_KEY = (New-Secret 32) }; Info '补充了缺失的 YII_COOKIE_VALIDATION_KEY' }
  Ok '.env 已存在，保持原有配置'
}
$Cfg = Read-EnvFile $EnvFile
$Mode = if ($Cfg['WCU_LOCAL_DB']) { $Cfg['WCU_LOCAL_DB'] } else { 'external' }   # .env from before this script: its own server
$DbPortUse = [int]$Cfg['DB_PORT']
foreach ($d in @('storage\runtime', 'storage\logs', 'storage\cache', 'storage\uploads', 'public\uploads', 'data\team\uploads', 'data\personal\uploads', 'public\assets\media\videos')) {
  New-Item -ItemType Directory -Force -Path (Join-Path $Root $d) | Out-Null
}

# ---------------------------------------------------------------------------------------------------------------
Step "4/7 MariaDB（$(if ($Mode -eq 'private') { "项目专用实例，端口 $DbPortUse，数据在 storage\local-db" } else { "已有服务器 $($Cfg['DB_HOST']):$DbPortUse" })）"
$AdminUser = 'root'; $AdminPass = ''
if ($Mode -eq 'private') {
  $bins = New-Object System.Collections.Generic.List[string]
  if ($MariaDbBin) { $bins.Add($MariaDbBin) }
  if ($env:WCU_MARIADB_BIN) { $bins.Add($env:WCU_MARIADB_BIN) }
  foreach ($x in @('C:\xampp\mysql\bin', 'D:\xampp\mysql\bin', 'E:\xampp\mysql\bin')) { $bins.Add($x) }
  Get-ChildItem 'C:\Program Files' -Directory -Filter 'MariaDB*' -ErrorAction SilentlyContinue | ForEach-Object { $bins.Add((Join-Path $_.FullName 'bin')) }
  $Bin = $null
  foreach ($b in $bins) {
    $mysqld = Join-Path $b 'mysqld.exe'
    if (-not (Test-Path -LiteralPath $mysqld)) { continue }
    $ver = (& $mysqld --version 2>$null) -join ' '
    if ($ver -match 'MariaDB' -and $ver -match 'Ver\s+(\d+\.\d+)') { if ([version]$Matches[1] -ge [version]'10.4') { $Bin = $b; $MariaVer = $ver; break } }
  }
  if (-not $Bin) {
    Fail '没有找到 MariaDB 10.4+ 程序（mysqld.exe）。' @"
解决方法：
  1. 安装 XAMPP（自带 MariaDB），默认目录 C:\xampp 会被自动识别；其他目录请运行：
       deploy-local.bat -MariaDbBin D:\XAMPP\mysql\bin
  2. 或者安装 MariaDB 10.6+（https://mariadb.org/download/）。
  注意：MySQL 8 不受支持（data/install.sql 的长唯一键只能在 MariaDB 上建立）。
"@
  }
  Ok "MariaDB 程序：$Bin"
  $DataDir = Join-Path $LocalDbDir 'data'; $Ini = Join-Path $LocalDbDir 'my.ini'
  if (-not (Test-Path -LiteralPath (Join-Path $DataDir 'mysql'))) {
    if (Test-Port $DbPortUse) { Fail "端口 $DbPortUse 已被 $(Get-PortOwner $DbPortUse) 占用，无法创建项目专用数据库。" "请关闭该程序，或在 .env 中把 DB_PORT 改成其他空闲端口后重新运行。" }
    New-Item -ItemType Directory -Force -Path $LocalDbDir | Out-Null
    Info '首次运行：初始化项目专用数据目录……'
    $initLog = Join-Path $LocalDbDir 'install.log'
    & (Join-Path $Bin 'mysql_install_db.exe') "--datadir=$DataDir" "--port=$DbPortUse" *> $initLog
    if ($LASTEXITCODE -ne 0 -or -not (Test-Path -LiteralPath (Join-Path $DataDir 'mysql'))) { Fail "mysql_install_db 失败，详见 $initLog" }
  }
  $fwd = $DataDir.Replace('\', '/')
  $iniText = "[mysqld]`r`ndatadir=$fwd`r`nport=$DbPortUse`r`nbind-address=127.0.0.1`r`ncharacter-set-server=utf8mb4`r`ncollation-server=utf8mb4_unicode_ci`r`ninnodb_buffer_pool_size=128M`r`nmax_allowed_packet=64M`r`nlog-error=$($LocalDbDir.Replace('\', '/'))/mariadb-error.log`r`n[client]`r`nport=$DbPortUse`r`ndefault-character-set=utf8mb4`r`n"
  [System.IO.File]::WriteAllText($Ini, $iniText, (New-Object System.Text.UTF8Encoding($false)))
  if (Test-Port $DbPortUse) { Ok "MariaDB 已在端口 $DbPortUse 运行（$(Get-PortOwner $DbPortUse)）" }
  else {
    $dbPid = Start-Detached (Join-Path $Bin 'mysqld.exe') @("--defaults-file=$Ini") $LocalDbDir
    Set-Content -LiteralPath (Join-Path $RuntimeDir 'local-mariadb.pid') -Value $dbPid
    Set-Content -LiteralPath (Join-Path $RuntimeDir 'local-mariadb.bin') -Value $Bin
    $deadline = (Get-Date).AddSeconds(30)
    while (-not (Test-Port $DbPortUse)) {
      if ((Get-Date) -gt $deadline -or -not (Get-Process -Id $dbPid -ErrorAction SilentlyContinue)) {
        $tail = if (Test-Path -LiteralPath (Join-Path $LocalDbDir 'mariadb-error.log')) { (Get-Content -LiteralPath (Join-Path $LocalDbDir 'mariadb-error.log') -Tail 8) -join "`n" } else { '' }
        Fail "项目专用 MariaDB 没能在端口 $DbPortUse 启动。" "日志（storage\local-db\mariadb-error.log）：`n$tail"
      }
      Start-Sleep -Milliseconds 300
    }
    Ok "已启动项目专用 MariaDB（PID $dbPid，端口 $DbPortUse，仅监听 127.0.0.1）"
  }
} else {
  if ($Cfg['DB_HOST'] -in @('127.0.0.1', 'localhost') -and -not (Test-Port $DbPortUse)) {
    Fail "无法连接数据库 $($Cfg['DB_HOST']):$DbPortUse（端口没有在监听）。" "请先在 XAMPP 控制面板启动 MySQL/MariaDB，或检查 .env 中的 DB_HOST / DB_PORT。"
  }
}

$env:WCU_DB_HOST = $Cfg['DB_HOST']; $env:WCU_DB_PORT = "$DbPortUse"; $env:WCU_DB_NAME = $Cfg['DB_DATABASE']
$env:WCU_DB_USER = $Cfg['DB_USERNAME']; $env:WCU_DB_PASS = $Cfg['DB_PASSWORD']; $env:WCU_ADMIN_USER = $AdminUser; $env:WCU_ADMIN_PASS = $AdminPass
$setup = (& $PhpExe @PhpArgs (Join-Path $Root 'scripts\local\db-setup.php') 2>&1) | Select-Object -Last 1
$res = $null; try { $res = $setup | ConvertFrom-Json } catch { }
if ($res -and -not $res.ok -and $res.needAdmin -and $Mode -ne 'private') {
  Info "需要数据库管理员账号来创建数据库 $($Cfg['DB_DATABASE'])（只使用这一次，不会保存）。"
  $u = Read-Host '    管理员用户名（直接回车 = root）'; if ($u) { $env:WCU_ADMIN_USER = $u }
  $sec = Read-Host '    管理员密码' -AsSecureString
  $env:WCU_ADMIN_PASS = [Runtime.InteropServices.Marshal]::PtrToStringAuto([Runtime.InteropServices.Marshal]::SecureStringToBSTR($sec))
  $setup = (& $PhpExe @PhpArgs (Join-Path $Root 'scripts\local\db-setup.php') 2>&1) | Select-Object -Last 1
  $res = $null; try { $res = $setup | ConvertFrom-Json } catch { }
}
foreach ($k in @('WCU_DB_PASS', 'WCU_ADMIN_PASS')) { Remove-Item "Env:$k" -ErrorAction SilentlyContinue }
if (-not $res) { Fail "数据库检查脚本没有返回结果：$setup" }
if (-not $res.ok) {
  $hint = if ($res.error -match 'MySQL') { '本项目只支持 MariaDB 10.4+。请不带 -UseExistingDb 运行（使用项目专用 MariaDB），或把 .env 指向一台 MariaDB。' } else { '请检查 .env 中的 DB_* 配置，以及数据库是否已经启动。' }
  Fail "数据库：$($res.error)" $hint
}
$HadSchema = [bool]$res.schema
Ok "$($res.version)；数据库 $($Cfg['DB_DATABASE']) $(if ($res.created) { '已新建' } elseif ($HadSchema) { '已存在（保留现有数据）' } else { '已存在（空库）' })"

# ---------------------------------------------------------------------------------------------------------------
Step '5/7 Yii2 安装与迁移（app/install：建表、迁移、空库才导入公开数据、管理员只创建一次）'
& $PhpExe @PhpArgs yii app/install
if ($LASTEXITCODE -ne 0) { Fail "php yii app/install 失败（退出码 $LASTEXITCODE）。" "详见上方输出和 storage\logs\console.log。" }
& $PhpExe @PhpArgs yii migrate/up --interactive=0 | Out-Null
if ($LASTEXITCODE -ne 0) { Fail 'php yii migrate/up 失败。' }
if (-not $HadSchema) { & $PhpExe @PhpArgs yii release/metadata | Out-Null }   # one-time display metadata, as on the server
& $PhpExe @PhpArgs yii cache/flush-all --interactive=0 | Out-Null
$mc = (& $PhpExe @PhpArgs yii media/check 2>&1) -join "`n"
if ($LASTEXITCODE -ne 0) { Write-Host $mc; Fail 'php yii media/check 未通过（上传目录或扩展有问题）。' }
Ok 'Yii2 数据库结构、公开数据与管理员账号就绪'

# ---------------------------------------------------------------------------------------------------------------
Step "6/7 启动网站 http://127.0.0.1:$Port"
$PidFile = Join-Path $RuntimeDir 'local-server.pid'
$running = $false
if (Test-Port $Port) {
  $old = if (Test-Path -LiteralPath $PidFile) { [int](Get-Content -LiteralPath $PidFile) } else { 0 }
  $owner = (Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue | Select-Object -First 1).OwningProcess
  if ($old -and $owner -eq $old) { $running = $true; Ok "网站已在运行（PID $old），沿用" }
  else { Fail "端口 $Port 已被 $(Get-PortOwner $Port) 占用。" "请关闭占用该端口的程序，或换一个端口运行：deploy-local.bat -Port 8090" }
}
if (-not $running) {
  $srv = @($PhpArgs) + @('-d', 'display_errors=0', '-d', 'log_errors=1', '-d', "error_log=$LogDir\local-php-error.log",
         '-d', 'upload_max_filesize=210M', '-d', 'post_max_size=220M', '-d', 'memory_limit=512M', '-d', 'max_execution_time=300',
         '-S', "127.0.0.1:$Port", '-t', 'public', 'public/index.php')
  $webPid = Start-Detached $PhpExe $srv $Root
  Set-Content -LiteralPath $PidFile -Value $webPid
  $deadline = (Get-Date).AddSeconds(20)
  while (-not (Test-Port $Port)) {
    if ((Get-Date) -gt $deadline -or -not (Get-Process -Id $webPid -ErrorAction SilentlyContinue)) { Fail "PHP 内置服务器没能在端口 $Port 启动。" "查看 storage\logs\local-php-error.log。" }
    Start-Sleep -Milliseconds 250
  }
  Ok "PHP 内置服务器已启动（PID $webPid）"
}
# The site now answers on -Port: keep APP_URL in step (only for local loopback URLs, only after a successful start).
if ($Cfg['APP_URL'] -match '^http://127\.0\.0\.1:(\d+)$' -and [int]$Matches[1] -ne $Port) { Set-EnvValues $EnvFile @{ APP_URL = "http://127.0.0.1:$Port" }; Info "APP_URL 已更新为 http://127.0.0.1:$Port" }

# ---------------------------------------------------------------------------------------------------------------
Step '7/7 健康检查'
$base = "http://127.0.0.1:$Port"; $bad = @()
foreach ($t in @(@('/', 200), @('/teams', 200), @('/worldcup', 200), @('/forum', 200), @('/news', 200), @('/login', 200), @('/api/v1/session', 200), @('/admin', 302), @('/.env', 404))) {
  $code = Get-Status ($base + $t[0])
  Info ("{0,-18} HTTP {1}（应为 {2}）" -f $t[0], $code, $t[1])
  if ($code -ne $t[1]) { $bad += $t[0] }
}
if ($bad) {
  $tail = @(); foreach ($f in @('local-php-error.log', 'yii2.log')) { $p = Join-Path $LogDir $f; if (Test-Path -LiteralPath $p) { $tail += "--- storage\logs\$f"; $tail += (Get-Content -LiteralPath $p -Tail 6) } }
  Fail "健康检查未通过：$($bad -join ', ')" ($tail -join "`n")
}
$Cfg = Read-EnvFile $EnvFile
Write-Host ''
Write-Host '============================================================' -ForegroundColor Green
Write-Host " 部署完成，网站运行中：$base/" -ForegroundColor Green
Write-Host " 管理后台：$base/admin   用户名：$($Cfg['ADMIN_USERNAME'])" -ForegroundColor Green
if (-not $HadSchema) { Write-Host " 初始管理员密码：$($Cfg['ADMIN_PASSWORD'])（已保存在 .env 的 ADMIN_PASSWORD）" -ForegroundColor Yellow }
else { Write-Host ' 管理员密码：见 .env 的 ADMIN_PASSWORD（如后来修改过，以修改后的为准）' }
Write-Host ' 停止网站：双击 stop-local.bat    再次启动：双击 deploy-local.bat' -ForegroundColor Green
Write-Host '============================================================' -ForegroundColor Green
if (-not $NoBrowser) { Start-Process "$base/" }
exit 0
