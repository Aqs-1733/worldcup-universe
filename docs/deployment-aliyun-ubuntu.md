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
| 目录 | `/var/www/worldcup-universe`（分支 `deploy/aliyun-production`） |

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
git clone -b deploy/aliyun-production https://github.com/Aqs-1733/worldcup-universe.git /var/www/worldcup-universe
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
