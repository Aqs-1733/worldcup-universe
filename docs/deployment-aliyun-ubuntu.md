# 阿里云 ECS 部署记录（Ubuntu 22.04 + Nginx + PHP-FPM 8.1 + MariaDB 10.6）

本文是 `docs/deployment-oracle-ubuntu.md` 的阿里云补充：部署流程、脚本、目录结构、备份恢复、更新回滚与那份文档完全相同，这里只记录阿里云环境的差异和 2026-11-08 的实际部署结果。网站仍是 PHP + Yii2 原生 MVC，没有改动业务路由和数据模型。

## 1. 环境

| 项目 | 值 |
|---|---|
| 实例 | 阿里云 ECS 中国香港 D，2 vCPU x86_64，约 1.7 GiB 内存，40 GiB 系统盘 |
| 系统 | Ubuntu 22.04.5 LTS（内核 5.15），未做发行版升级 |
| 软件 | Nginx 1.18.0、PHP 8.1.2（FPM）、MariaDB 10.6.23、Composer 2.2.6（均为 Ubuntu 官方源 / 阿里云镜像） |
| 框架 | Yii 2.0.55 |
| 部署用户 | `wcu-deploy`（sudo 需要密码，没有配置免密 sudo） |
| 目录 | `/var/www/worldcup-universe`（跟踪 GitHub `main`；适配提交最初在分支 `deploy/aliyun-production`） |

## 2. 与 Oracle 文档的差异

1. **网络**：阿里云 Ubuntu 镜像的主机防火墙默认不拦截流量（ufw inactive，iptables 全部 ACCEPT），入站过滤由 **安全组** 负责。不要使用 `deploy.sh --open-firewall`（那是 Oracle 镜像 iptables 规则的放行）。
   安全组入方向只放行：TCP 22（建议限制为自己的 IP）、TCP 80、TCP 443，来源 `0.0.0.0/0`。**不要放行 3306**；MariaDB 本身也只监听 `127.0.0.1`（`deploy/mariadb/60-worldcup-universe.cnf`）。安全组规则本身不收费。
   可选：主机层再启用 ufw（`ufw allow 22/tcp; ufw allow 80/tcp; ufw allow 443/tcp; ufw enable`），作为安全组之外的第二道防线。
2. **小内存调优**：`deploy.sh` 现在安装 `deploy/mariadb/60-worldcup-universe.cnf`（buffer pool 256M、max_connections 40、关闭 performance_schema、只监听回环地址），内容变化时才重启 MariaDB。PHP-FPM 在内存 < 2 GB 时自动取 `pm.max_children=5`。另加 1 GiB swap（`vm.swappiness=10`）：
   ```bash
   sudo fallocate -l 1G /swapfile && sudo chmod 600 /swapfile && sudo mkswap /swapfile && sudo swapon /swapfile
   echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab
   echo 'vm.swappiness=10' | sudo tee /etc/sysctl.d/60-wcu-swap.conf && sudo sysctl -p /etc/sysctl.d/60-wcu-swap.conf
   ```
3. **PHP 8.1 兼容修复**：`services/NewsImportService.php` 无条件使用了 `CURLSSLOPT_NATIVE_CA`，这个常量在 PHP 8.2 才有，所以在 PHP 8.1 上 7 个 RSS 来源全部失败。现在改为只在 Windows 且常量存在时使用（与 `scripts/fetch-open-videos.php` 的写法一致）；Linux 本来就使用系统 CA，行为没有变化。
4. **本地视频**：Wikimedia 对 ECS 的 IP 返回 HTTP 429，`php scripts/fetch-open-videos.php` 在服务器上下载失败。改为从开发机用 `scp` 上传同一批文件（上传属于入站流量），再校验 sha256，然后执行 `chgrp www-data`、`chmod 664`。

## 3. 首次部署命令（实际执行）

```bash
sudo mkdir -p /var/www && sudo chown wcu-deploy:wcu-deploy /var/www
git clone -b main https://github.com/Aqs-1733/worldcup-universe.git /var/www/worldcup-universe
cd /var/www/worldcup-universe
sudo bash scripts/deploy.sh --install-packages --skip-tls      # 没有域名时只配置 HTTP
```

拿到域名、A 记录生效后：

```bash
sudo bash scripts/deploy.sh --domain <域名> --email <邮箱>     # 申请 Let's Encrypt 证书，切换到 HTTPS + HSTS
```

HTTPS 生效前，生产环境会拒绝来自公网 HTTP 的登录和注册请求（返回 403），所以不要设置 `ALLOW_INSECURE_LOGIN=1`。需要提前进入后台时，用 SSH 隧道：`ssh -L 8080:127.0.0.1:80 wcu-deploy@<IP>`，然后打开 `http://127.0.0.1:8080/login`。

## 4. 验收测试方式

写入型回归测试不在生产库上运行，而是在同一台服务器上另建一个隔离的 QA 实例：

- 代码：`/var/www/wcu-test`（与生产相同的 commit），数据库 `worldcup_universe_test`，账号 `wcu_test`（只有该库的权限）；
- Nginx 只监听 `127.0.0.1:8098` / `127.0.0.1:8088`，公网无法访问；使用与生产相同的 location 规则；
- 运行：`cd /var/www/wcu-test && WCU_TEST_URL=http://127.0.0.1:8098 ADMIN_PASSWORD=… python3 tests/<suite>.py`，测试结束后用 `php tests/cleanup.php qa_XXXXXXXX` 清理。

不再需要时可以删除 QA 实例：`sudo rm /etc/nginx/sites-enabled/wcu-test && sudo systemctl reload nginx`，删除 `/var/www/wcu-test`，再执行 `DROP DATABASE worldcup_universe_test; DROP USER 'wcu_test'@'localhost', 'wcu_test'@'127.0.0.1';`。

## 5. 费用注意

本部署没有使用任何额外收费的云资源：没有快照、云盘、负载均衡、RDS，也没有修改带宽或实例规格。备份只保存在本机 `/var/backups/worldcup-universe`，不能防止整机丢失，需要定期下载到本地：

```bash
scp -r wcu-deploy@<IP>:/var/backups/...   # 先在服务器上把要下载的文件复制到 wcu-deploy 有读权限的位置
```

请在阿里云控制台“费用与成本”中设置费用预警。预警只发通知，不会自动停止扣费；免费额度用完或试用到期（2027-02-08）之前，要先把备份下载到本地，再释放实例。

## 6. 运维手册

以下命令都在服务器的 `/var/www/worldcup-universe` 目录下执行。

### 6.1 更新（推荐方式：推送到 GitHub `main`）

只把**本地测试过的提交**推送到 `main`。服务器上的 `wcu-update.timer` 每 15 分钟检查一次，有新提交就运行 `scripts/update-production.sh`：

1. 加锁：与 `deploy.sh`、`restore.sh` 共用 `/run/lock/wcu-deploy.lock`，同一时间只能运行一个任务；维护模式下拒绝运行；
2. `git fetch`；没有新提交就直接退出。上次失败的提交会被跳过，直到有新提交或执行 `--clear-failure`；
3. 以下情况拒绝更新：服务器上被跟踪的文件有未提交修改、当前不在 `main` 分支、远程历史不是快进（例如有人强推）；
4. 在临时 worktree 中预检新提交：对所有 PHP 文件执行 `php -l`，对 `scripts/*.sh` 执行 `bash -n`；
5. 备份数据库和上传文件到 `/var/backups/worldcup-universe/pre-update/`（保留 30 天）；
6. 执行 `git merge --ff-only`，再执行 `deploy.sh --skip-backup`（Composer、Yii2 迁移、配置、重启服务）；
7. 健康检查：`/ /teams /news /forum /watch /login /api/v1/home /api/v1/session` 返回 200，`/admin` 返回 302，`/.env` 和 `/composer.json` 返回 404，`yii media/check` 通过，没有待执行的迁移。

失败时的处理：如果**没有执行新迁移**，自动把代码退回上一个提交并重新部署（数据库结构没有变化，所以这样做是安全的）；如果**已经执行了新迁移**，不自动回滚，在日志中打印恢复命令（见 6.4）。`.env`、管理员密码、数据库数据和上传文件都不会被重置。

```bash
sudo bash scripts/update-production.sh --check          # 只查看是否有更新
sudo bash scripts/update-production.sh                  # 立即更新（不用等定时器）
sudo bash scripts/update-production.sh --clear-failure  # 允许重试上次失败的提交
sudo systemctl disable --now wcu-update.timer           # 关闭自动更新（重新开启：enable --now）
```

### 6.2 状态与日志

```bash
systemctl list-timers 'wcu-*'                          # 新闻同步 / 备份 / 自动更新三个定时器
systemctl status nginx php8.1-fpm mariadb --no-pager
sudo tail -n 100 /var/log/worldcup-universe/update.log  # 自动更新记录（UPDATED / REFUSED / FAILED / ROLLED BACK）
journalctl -u wcu-update -n 100 --no-pager
journalctl -u wcu-news-sync -n 50 --no-pager            # 新闻同步；各来源错误也写在后台“新闻来源”页面
tail -n 50 storage/logs/yii2.log storage/logs/console.log storage/logs/php-fpm-error.log
sudo tail -n 50 /var/log/nginx/error.log
git log --oneline -3; git status -sb                    # 当前部署的提交
```

### 6.3 备份

- 自动备份：`wcu-backup.timer` 每天 03:30 运行，保留 14 天，备份在 `/var/backups/worldcup-universe/`；每次部署前备份在 `pre-deploy/`，每次更新前备份在 `pre-update/`，每次恢复前备份在 `pre-restore/`；
- 手动备份：`sudo bash scripts/backup.sh`；
- 下载到本地（备份放在同一台机器上，不能防止整机丢失）：
  ```bash
  sudo install -m 600 -o wcu-deploy /var/backups/worldcup-universe/db-<时间>.sql.gz /var/backups/worldcup-universe/files-<时间>.tar.gz /tmp/
  scp wcu-deploy@8.218.103.160:/tmp/{db,files}-<时间>.* .   # 在本地执行；完成后删除服务器 /tmp 中的副本
  ```

### 6.4 回滚

| 情况 | 命令 |
|---|---|
| 只回滚代码（新版本没有迁移，或迁移向后兼容） | `sudo -u wcu-deploy git reset --keep <上一个提交>` 然后 `sudo bash scripts/deploy.sh --skip-backup` |
| 回滚代码和数据（会丢失备份之后的所有写入） | 先执行上一行的 `git reset --keep`，再执行 `sudo bash scripts/restore.sh --db pre-update/db-….sql.gz --files pre-update/files-….tar.gz --yes`，最后执行 `deploy.sh --skip-backup` |
| 撤销单个迁移 | `sudo -u www-data php yii migrate/down 1 --interactive=0`（迁移必须实现了 down，并先确认会删除哪些数据） |

服务器的 `main` 被 `reset` 回旧提交后，自动更新会把它重新快进到 `origin/main`。如果 GitHub 上的问题提交还在，先 `disable` 定时器，或者推送一个修复提交。

### 6.5 2026-11-08 实测

`update-production.sh` 在生产服务器上用本机临时仓库测试了以下场景：已是最新、发现更新、跟踪文件被修改时拒绝、正常更新、PHP 语法错误在预检阶段被拦截且之后不再重试、首页 500 时自动回滚、推送修复提交后恢复更新、并发任务被锁阻止、执行了迁移后失败（不自动回滚，打印恢复步骤）。全部符合预期，数据计数前后一致。之后推送到 `main` 的真实提交由定时器自动部署。
