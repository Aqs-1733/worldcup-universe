<#
WorldCup Universe - 下载并解密生产环境的异地备份（由 fetch-backup.bat 调用）。

  fetch-backup.bat                                   取最新一份
  fetch-backup.bat -Snapshot 20261118-042355         取指定的一份
  fetch-backup.bat -Repo OWNER/worldcup-universe-backups -SecretKey D:\keys\backup-secret.key

需要：Git for Windows（用你自己的 GitHub 账号访问私有备份仓库）、备份私钥、运行过一次 deploy-local.bat（用于找到 PHP）。
结果放在项目的 backups\<时间>\ 目录：db-*.sql.gz（数据库）、files-*.tar.gz（上传文件、课程作业、.env）、summary.json。
backups\ 含有生产数据和密钥，已被 .gitignore 排除，请勿提交、勿外传。下一步：restore-local.bat 验证或恢复。
#>
[CmdletBinding()]
param(
  [string]$Repo = '',
  [string]$Snapshot = '',
  [string]$SecretKey = '',
  [string]$OutDir = ''
)
$ErrorActionPreference = 'Stop'
$Root = (Resolve-Path (Join-Path $PSScriptRoot '..\..')).Path
$Home2 = Join-Path $env:USERPROFILE '.worldcup-universe'
function Fail([string]$m, [string]$hint = '') { Write-Host ''; Write-Host "[失败] $m" -ForegroundColor Red; if ($hint) { Write-Host $hint -ForegroundColor Yellow }; exit 1 }
function Ok([string]$m) { Write-Host "    [OK] $m" -ForegroundColor Green }

$cfgFile = Join-Path $Home2 'offsite.json'
$cfg = if (Test-Path -LiteralPath $cfgFile) { Get-Content -LiteralPath $cfgFile -Raw -Encoding UTF8 | ConvertFrom-Json } else { $null }
if (-not $Repo -and $cfg) { $Repo = $cfg.repo }
if (-not $Repo) { Fail '不知道备份仓库是哪个。' "请运行：fetch-backup.bat -Repo OWNER/worldcup-universe-backups（或在 $cfgFile 中写 {""repo"":""OWNER/NAME""}）" }
if (-not $SecretKey) { $SecretKey = Join-Path $Home2 'backup-secret.key' }
if (-not (Test-Path -LiteralPath $SecretKey)) { Fail "找不到备份私钥：$SecretKey" '私钥由团队保管（生成时只保存在负责人的电脑上）。没有私钥就无法解密备份。' }
if (-not $OutDir) { $OutDir = Join-Path $Root 'backups' }
$git = Get-Command git -ErrorAction SilentlyContinue
if (-not $git) { Fail '没有找到 git。' '请安装 Git for Windows：https://git-scm.com/download/win' }
$phpRec = Join-Path $Root 'storage\runtime\local-php.json'
if (-not (Test-Path -LiteralPath $phpRec)) { Fail '还不知道使用哪个 PHP。' '请先运行一次 deploy-local.bat（它会检查并记录 PHP），再运行本脚本。' }
$php = Get-Content -LiteralPath $phpRec -Raw -Encoding UTF8 | ConvertFrom-Json
$PhpExe = $php.exe; $PhpArgs = [string[]]@($php.args)
$hasSodium = (& $PhpExe @PhpArgs -r "echo extension_loaded('sodium') ? 'yes' : 'no';")
if ($hasSodium -ne 'yes') { Fail "PHP（$PhpExe）没有 sodium 扩展，无法解密。" '请在 php.ini 中启用 extension=sodium（XAMPP / 官方 PHP 都自带 php_sodium.dll）。' }

Write-Host "==> 同步私有备份仓库 $Repo"
$clone = Join-Path $Home2 'backup-repo'
New-Item -ItemType Directory -Force -Path $Home2 | Out-Null
if (-not (Test-Path -LiteralPath (Join-Path $clone '.git'))) {
  & git clone -q "https://github.com/$Repo.git" $clone
  if ($LASTEXITCODE -ne 0) { Fail "git clone 失败。" '请确认已用有权限的 GitHub 账号登录（例如运行 gh auth login 或在弹出的窗口中登录），且仓库名正确。' }
} else {
  & git -C $clone pull -q --ff-only
  if ($LASTEXITCODE -ne 0) { Fail 'git pull 失败（网络或权限问题）。' }
}
if (-not $Snapshot) { $Snapshot = (Get-Content -LiteralPath (Join-Path $clone 'LATEST') -ErrorAction SilentlyContinue | Select-Object -First 1) }
$snapDir = Join-Path $clone "snapshots\$Snapshot"
if (-not $Snapshot -or -not (Test-Path -LiteralPath $snapDir)) {
  $list = (Get-ChildItem -LiteralPath (Join-Path $clone 'snapshots') -Directory -ErrorAction SilentlyContinue | Sort-Object Name -Descending | Select-Object -First 10 -ExpandProperty Name) -join ', '
  Fail "仓库中没有快照 '$Snapshot'。" "可用的快照：$list"
}
$summary = Get-Content -LiteralPath (Join-Path $snapDir 'summary.json') -Raw -Encoding UTF8 | ConvertFrom-Json
Ok "快照 $Snapshot（$($summary.created)，应用版本 $($summary.app_commit)，$($summary.parts.Count) 个分卷）"

Write-Host '==> 解密（同时校验每一块数据都未被修改）'
$dest = Join-Path $OutDir $Snapshot
New-Item -ItemType Directory -Force -Path $dest | Out-Null
$tar = Join-Path $dest 'snapshot.tar'
$parts = @($summary.parts | ForEach-Object { Join-Path $snapDir $_ })
& $PhpExe @PhpArgs (Join-Path $Root 'scripts\backup-crypt.php') decrypt $SecretKey $tar @parts
if ($LASTEXITCODE -ne 0) { Fail '解密失败（见上方原因）。' }
$sha = (Get-FileHash -LiteralPath $tar -Algorithm SHA256).Hash.ToLowerInvariant()
if ($sha -ne $summary.plain_sha256) { Remove-Item -LiteralPath $tar; Fail '解密结果的 SHA-256 与 summary.json 不一致。' }
Ok "解密完成，SHA-256 一致（$([math]::Round((Get-Item -LiteralPath $tar).Length / 1MB, 1)) MB）"

& "$env:SystemRoot\System32\tar.exe" -xf $tar -C $dest
if ($LASTEXITCODE -ne 0) { Fail 'tar 解包失败。' }
Remove-Item -LiteralPath $tar
Copy-Item -LiteralPath (Join-Path $snapDir 'summary.json') -Destination $dest -Force
Ok "已解包到 $dest"
Get-ChildItem -LiteralPath $dest | ForEach-Object { Write-Host ("      {0,-36} {1,10:N0} KB" -f $_.Name, ($_.Length / 1KB)) }
Write-Host ''
Write-Host '下一步：' -ForegroundColor Cyan
Write-Host "  验证这份备份能否恢复（导入独立的数据库，不影响本地网站）：restore-local.bat -Snapshot $Snapshot"
Write-Host "  用它替换本地网站的数据（会先备份本地现有数据）：      restore-local.bat -Snapshot $Snapshot -IntoLocalSite"
Write-Host '注意：该目录包含生产数据库、用户信息和 .env 密钥，请妥善保管，用完可删除。' -ForegroundColor Yellow
exit 0
