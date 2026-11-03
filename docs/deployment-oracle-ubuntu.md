# Oracle Cloud Always Free 生产部署（Ubuntu + Nginx + PHP-FPM + MariaDB）

本文是课程“团队作业 5——部署文档”的服务器部分：从一台全新的 Oracle Cloud Ubuntu 实例，到 HTTPS 公网演示、文件上传、新闻定时同步、备份与恢复、回滚。网站仍是 PHP + Yii2 原生 MVC，部署只增加服务器配置与运维脚本，不改变业务路由和数据。

## 1. 架构与目录

```
Internet ──443/80──> Nginx ──unix socket──> PHP-FPM 池 worldcup-universe（www-data）──> MariaDB（127.0.0.1:3306）
                       │ 直接服务：public/assets、public/uploads、/stream/<视频>（支持 Range）
systemd 定时器：wcu-news-sync.timer（每 10 分钟 php yii news/sync） · wcu-backup.timer（每天 03:30 备份）
```

| 路径（默认 `/var/www/worldcup-universe`） | 说明 | 是否公开 |
|---|---|---|
| `public/` | **唯一 Web 根目录**，只执行 `public/index.php` | 是 |
| `public/uploads/YYYY/MM/<32位随机名>` | 后台上传的图片/视频（媒体资产、成员照片、球员照片、国旗、新闻封面） | 是（静态、禁止执行、沙箱 CSP） |
| `data/team/`、`data/personal/` | 课程作业；后台上传的保存在其下 `uploads/` | 否，仅经 `/downloads/<id>` 控制器下载，且记录须“前台可见” |
| `data/install.sql` | 课程要求的数据库 SQL | 否 |
| `.env`、`vendor/`、`storage/`（runtime、logs、缓存、会话） | 配置、依赖、日志 | 否（在 Web 根之外，且 Nginx 拒绝所有点文件） |
| `/var/backups/worldcup-universe/` | 数据库与文件备份（root 0700） | 否 |

代码文件属于部署用户（如 `ubuntu`），`www-data` 只能写 `storage/`、`public/uploads/`、`data/team|personal/`、`public/assets/media/videos/`，不能改代码。

## 2. 数据库选择：必须 MariaDB 10.4+

`data/install.sql` 在 `news_sources.rss_url`（VARCHAR 800）、`news_articles.source_url`、`source_files.source_path`（VARCHAR 900）上声明了 UNIQUE。utf8mb4 下超过 3072 字节，**MySQL 8 会报错 1071 无法建表**；MariaDB 10.4+ 用哈希实现长唯一键，正常安装（本次在 MariaDB 10.4.32 全新安装通过，在 MySQL 8.0.19 上复现了 1071 失败）。Ubuntu 22.04 自带 MariaDB 10.6，24.04 自带 10.11，`deploy.sh` 安装 `mariadb-server`，检测到 MySQL 或低于 10.4 的 MariaDB 时停止。为避免改动已有表结构，本次没有改动这些索引。

MariaDB 10.7 起 `ROW_NUMBER` 是保留字，原 `source_records.row_number` 列在 10.11 上全新安装会报 1064；已在 `install.sql` 中给该列名加反引号（对已有数据库无影响）。

## 3. Oracle Cloud 准备

1. 创建实例：推荐 **Ubuntu 24.04**（PHP 8.3 + MariaDB 10.11 + Nginx 1.24，已在该组合上实测全流程）；22.04 也可用。Always Free 可选 VM.Standard.E2.1.Micro（1 GB 内存）或 Ampere A1（ARM64，最多 4 核 24 GB；所用 apt 包与 Composer 依赖均无架构相关二进制，但未在 ARM64 上实测）。1 GB 机型建议加 2 GB swap：
   ```bash
   sudo fallocate -l 2G /swapfile && sudo chmod 600 /swapfile && sudo mkswap /swapfile && sudo swapon /swapfile
   echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab
   ```
2. 网络：在 VCN 的 Security List（或 NSG）添加入站规则 TCP 80、443（来源 0.0.0.0/0）。Oracle 的 Ubuntu 镜像自带 iptables 规则会拒绝 80/443，`deploy.sh --open-firewall` 会插入放行规则并用 `netfilter-persistent` 保存。
3. 域名：把 A 记录指向实例公网 IP。Let's Encrypt 不给纯 IP 签证书。没有域名时，可先用 `http://<公网IP>` 冒烟测试（此时 Cookie 不带 Secure），或使用指向该 IP 的免费动态域名。

## 4. 一键部署（首次）

```bash
sudo mkdir -p /var/www && sudo chown "$USER" /var/www
git clone https://github.com/Aqs-1733/worldcup-universe.git /var/www/worldcup-universe
cd /var/www/worldcup-universe
git checkout deploy/oracle-production        # 或已合并后的 main
sudo bash scripts/deploy.sh --install-packages --open-firewall \
     --domain your.domain.example --email you@example.com
```

脚本按顺序完成（可重复执行，已有数据不会被覆盖）：

1. 安装 nginx、mariadb-server、php-fpm 与扩展（pdo_mysql、mbstring、curl、xml、zip、fileinfo）、composer、certbot；
2. 首次运行由 `deploy/env.production.example` 生成 `.env`：随机 `DB_PASSWORD`、`ADMIN_PASSWORD`、64 位十六进制 `YII_COOKIE_VALIDATION_KEY`，`APP_ENV=production`，权限 640 `部署用户:www-data`；
3. 以 root 本地 socket 建库 `worldcup_universe` 和最小权限账号 `wcu_app`（仅该库权限）；
4. 设置可写目录与 setgid 权限；已有库时先自动备份到 `/var/backups/worldcup-universe/pre-deploy/`；
5. `composer install --no-dev --optimize-autoloader`；
6. 以 `www-data` 运行 `php yii app/install`（建表、迁移、空库才导入体育种子、管理员只创建一次）和 `migrate/up`；全新库额外运行 `release/metadata` 写入团队成员与场馆中文名（之后不再运行，以免覆盖后台修改）；清缓存；`php yii media/check` 检查扩展和目录；
7. 生成 PHP-FPM 独立池（上传上限取 `.env` 中最大值，`disable_functions` 禁止执行外部程序）、Nginx 站点、HTTPS 证书（webroot 方式，续期后自动 reload）；
8. 安装并启用 `wcu-news-sync.timer`、`wcu-backup.timer`；
9. 冒烟测试：`/` 必须 200，`/.env` 必须 404，否则脚本以失败退出。

管理员账号：`.env` 中 `ADMIN_USERNAME`（默认 admin），初始密码：`sudo grep ADMIN_PASSWORD /var/www/worldcup-universe/.env`。账号建好后，再改 `.env` 不会修改已存密码哈希。

初始化只能通过服务器上的 root shell 进行：网站没有任何安装或初始化网页。

## 5. 生产配置要点

| 项目 | 实现 |
|---|---|
| HTTPS | 443 + HTTP/2，80 → 301；HSTS `max-age=31536000`；证书由 certbot.timer 自动续期 |
| Cookie | `APP_ENV=production` 且 `APP_URL` 为 https 时，会话、身份、CSRF Cookie 均带 `Secure; HttpOnly; SameSite=Lax`；`SESSION_SECURE_COOKIE=1/0` 可强制 |
| Cookie Validation Key | 生产环境必须在 `.env` 固定（≥32 字符），缺失直接报错，避免重启后会话失效 |
| 安全响应头 | `X-Content-Type-Options`、`X-Frame-Options`、`Referrer-Policy`、`Permissions-Policy`、HSTS；隐藏 `X-Powered-By` 与 Nginx 版本 |
| 禁止访问 | 所有点文件（`.env`、`.git`）404；`public/` 外文件不可达；`public/` 下除 `index.php` 外任何 `.php` 404；`/uploads/` 中脚本、HTML、SVG、JS 一律 403，其他文件附带 `sandbox` CSP |
| 日志 | Yii 错误：`storage/logs/yii2.log`（Web）、`storage/logs/console.log`（命令/新闻同步）；PHP：`storage/logs/php-fpm-error.log`；Nginx：`/var/log/nginx/`；定时任务：`journalctl -u wcu-news-sync` |
| 会话 | 保存在 `storage/runtime/sessions`，开启 1% 概率 GC（Ubuntu 默认只清理系统目录） |
| 静态资源 | gzip；CSS/JS 缓存 1 天（布局带 `?v=mtime`）；图片、字体、视频 30 天；`/stream/*.mp4` 由 Nginx 直接按 Range 服务，不占 PHP 进程 |
| Linux 兼容 | 已核对类名与目录大小写（`App\` → `app/`，`app\…` → 小写目录）；路径使用 `/`；脚本 LF 换行；`yii` 设为可执行 |

## 6. 后台文件上传

后台（`/admin`，管理员登录）以下表单出现“选择文件”，可预览当前文件、本地预览待上传文件、上传替换：

| 后台模块 | 字段 | 允许类型 | 前台展示位置 |
|---|---|---|---|
| 媒体资产 | path | jpg/png/webp/gif ≤10 MB；mp4/m4v/webm ≤200 MB | `home-stadium` 首页第二幕背景、`home-final` 决赛幕背景、`home-hero-video` 首页循环视频；其他已批准视频进入 `/watch` 影像馆 |
| 项目团队 | photo_url | 图片 | `/team` 成员卡、首页“项目团队” |
| 球员 | photo_url | 图片 | 球员页与卡片（替换照片不再取消官方核验标记） |
| 球队 | flag_url | 图片 | 使用国旗图片的列表 |
| 新闻 | 封面（raw_payload_json.image） | 图片 | 新闻列表与详情；RSS 再同步会保留后台上传的封面 |
| 课程交付 | storage_path | pdf/doc/docx/ppt/pptx/xlsx/zip/rar/7z/md/txt/mp4 ≤100 MB | `/team#downloads`，下载文件名为登记标题 |

上传校验（`services/MediaUploadService.php`）：必须管理员 + CSRF；扩展名白名单；`finfo` 识别的 MIME 必须与扩展名一致；图片再用 `getimagesize` 校验并拒绝超过 4000 万像素或内含 `<?php`、`<script` 的文件；拒绝 SVG/HTML/JS 和任何位置出现 `.php` 等可执行扩展的文件名；保存为 32 位随机十六进制名，目录由程序决定，用户输入不参与路径；保存失败或表单校验失败会删除已存文件。大小上限由 `.env` 的 `UPLOAD_MAX_*_MB` 控制，PHP-FPM `upload_max_filesize`/`post_max_size` 与 Nginx 中 `/admin/` 的 `client_max_body_size` 由部署脚本按最大值同步；其他路由（论坛、个人中心、AI 识图 ≤5 MB）固定 16 MB，匿名请求无法上传大体积数据。

访问边界：`/uploads/` 是公开媒体，只放网页需要展示的文件；课程作业保存在 Web 根外，未勾选“前台可见”时访客无法下载（包括直接放进 `data/team` 的同名文件），管理员可在后台用“下载当前文件”查看。

替换文件后旧文件不会立即删除（便于回滚）。清理孤立文件（扫描所有表的文本字段，管理员粘贴到正文或其他字段的上传路径同样视为引用；`audit_logs` 除外；只处理程序生成的随机文件名）：

```bash
sudo -u www-data php yii media/prune            # 只列出无记录引用、超过 1 小时的文件
sudo -u www-data php yii media/prune --apply    # 确认后删除
```

上传文件不在 Git 中，`git pull` 和重新部署都不会动它们，并由每日备份覆盖。

## 7. 新闻同步（systemd）

- `wcu-news-sync.timer` 每 10 分钟启动一次 `wcu-news-sync.service`（`www-data` 身份运行 `php yii news/sync`）。
- 防重复：`flock -n` 保证同时只有一个定时任务；`NewsImportService` 内部再用 Yii `FileMutex`（操作系统文件锁，进程崩溃自动释放），与后台“刷新新闻”按钮共用。
- 失败记录与恢复：失败来源的错误写入 `news_sources.last_error`（后台“新闻来源”可见）和 `storage/logs/console.log`，命令返回非 0 使 systemd 记为失败；下一次定时自动重试。`Persistent=true` 保证停机期间错过的运行在开机后补跑。

```bash
systemctl list-timers 'wcu-*'
sudo systemctl start wcu-news-sync.service      # 立即同步一次
journalctl -u wcu-news-sync -n 50 --no-pager
```

Windows 本地开发仍可用原来的 `start_php.bat` / `scripts/start_news_worker.ps1`。

## 8. 备份与恢复

每天 03:30 自动执行 `scripts/backup.sh`，保留 14 天：

- `db-时间戳.sql.gz`：`mysqldump --single-transaction`（不锁表，含触发器/例程）；
- `files-时间戳.tar.gz`：`public/uploads`（后台公开媒体）、`data/team`、`data/personal`（课程文件）、`storage/uploads`（AI 识图私有上传）、`public/assets/media/videos`（本地视频）、`.env`。

备份目录权限 700、归档 600（含 `.env` 中的密钥，复制到机器外时请加密保存）。

手动备份：`sudo bash scripts/backup.sh`（可设 `BACKUP_DIR`、`BACKUP_KEEP_DAYS`）。数据库密码通过 0600 临时配置文件传给 mysqldump，不出现在命令行或进程列表。

恢复（目标库为 `.env` 中的库，需已存在；新服务器先运行一次 `deploy.sh`）：

```bash
ls /var/backups/worldcup-universe/
sudo bash scripts/restore.sh --db /var/backups/worldcup-universe/db-20261103-033000.sql.gz \
     --files /var/backups/worldcup-universe/files-20261103-033000.tar.gz          # 只显示将覆盖的内容
sudo bash scripts/restore.sh --db ... --files ... --yes                              # 真正执行
```

恢复脚本：没有 `--yes` 只做演练；执行前先把当前状态备份到 `pre-restore/`；暂停新闻定时器；导入数据库与文件并修正权限；最后执行 `migrate/up` 和清缓存（恢复旧备份后自动补齐新迁移）。`--keep-env` 保留当前 `.env`（换服务器、换数据库密码时使用）。

迁移到新服务器：新机执行第 4 节部署 → 把备份复制到新机 → `restore.sh --db … --files … --keep-env --yes`。若需要沿用旧会话，再把旧 `.env` 中的 `YII_COOKIE_VALIDATION_KEY` 写入新 `.env`。

## 9. 日常更新与回滚

更新：

```bash
cd /var/www/worldcup-universe
git fetch && git checkout <新版本标签或分支> && git pull --ff-only
sudo bash scripts/deploy.sh          # 自动先备份，再装依赖、迁移、重载服务
```

回滚代码（数据不变）：`git checkout <上一个提交或标签>` 后再运行 `sudo bash scripts/deploy.sh --skip-backup`。

本次唯一的新迁移 `m261103_000001_home_media_slots` 只新增两条 `media_assets` 记录；回退它：`sudo -u www-data php yii migrate/down 1 --interactive=0`。回退只删除与插入时完全一致的记录，管理员改过文件、说明或状态的记录会保留。

回滚数据：使用 `pre-deploy/` 或每日备份，按第 8 节 `restore.sh` 恢复。

撤销整个部署配置：`sudo rm /etc/nginx/sites-enabled/worldcup-universe /etc/php/*/fpm/pool.d/worldcup-universe.conf && sudo systemctl disable --now wcu-news-sync.timer wcu-backup.timer`，然后重载 nginx、php-fpm。

## 10. 仍需人工完成的事项

- 在 Oracle 控制台开放 80/443；配置域名 A 记录；
- 首次登录后台后妥善保管 `ADMIN_PASSWORD`（建议之后从 `.env` 删除该行，管理员已存在时不再需要）；
- 忘记或泄露管理员密码时，在服务器上重置（密码不进命令行历史，旧会话与“记住我”全部失效，操作写入审计日志）：
  ```bash
  cd /var/www/worldcup-universe
  sudo -u www-data php yii app/reset-password admin              # 交互输入两次，不回显
  sudo -u www-data php yii app/reset-password admin --generate   # 生成 20 位随机密码并只显示一次
  ```
- 如需 AI 功能，在 `.env` 填写 `ARK_API_KEY` 等（未填写时功能保持“未配置”状态，不伪造结果）；
- 把 `/var/backups/worldcup-universe` 定期复制到实例之外（如 Oracle Object Storage 或本地），单机备份不能防止整机丢失；
- 本地视频（`public/assets/media/videos/`，未纳入 Git）需要时用 `scp` 上传，或在后台“媒体资产”上传；
- `final-highlights` 等第三方嵌入视频依赖 YouTube/RTVE 在访问者网络中的可用性，部署无法保证。

## 11. 验证清单

```bash
curl -I https://your.domain.example/                 # 200，含 Strict-Transport-Security
curl -I https://your.domain.example/.env             # 404
curl -I https://your.domain.example/composer.json    # 404（交给 Yii 路由，不是文件）
sudo -u www-data php yii media/check                  # 全部 PASS
systemctl list-timers 'wcu-*'                         # 两个定时器均有下次运行时间
sudo bash scripts/backup.sh && ls -lh /var/backups/worldcup-universe/
```

后台冒烟：登录 `/admin` → 项目团队 → 编辑成员 → 选择图片 → 保存 → `/team` 与首页显示新照片；课程交付 → 新增 → 上传 PDF、先不勾选“前台可见” → 访客访问 `/downloads/<id>` 为 404 → 勾选后可下载。
