# 安装、采集与恢复

## 环境与全新安装

PHP 8.1+，扩展 pdo_mysql、mbstring、curl、SimpleXML、fileinfo；Composer；MySQL 8+ 或兼容 MariaDB/InnoDB。Python 的 pdfplumber 用于重新采集 FIFA PDF，网站运行本身不依赖 Python。

```powershell
Copy-Item .env.example .env
# 在本机编辑数据库连接和 ADMIN_PASSWORD（至少 12 位）
composer install --no-dev --prefer-dist
php yii app/install
php yii release/metadata
php -S 127.0.0.1:8080 -t public public/index.php
```

Web 根目录只能是 public。安装执行基础 34 表、5 个增量迁移、公开种子、管理员初始化；升级后共 48 表（含迁移历史）。非空体育库跳过种子，重复安装不覆盖已有密码。公开种子不包含用户、会话、密钥或论坛测试内容。不要把 `.env`、storage/runtime 或数据库目录上传仓库。

Windows 的 setup.ps1 / start_php.bat 提供同一流程；后者同时启动新闻进程。当前验收地址 127.0.0.1:8088 使用独立 MariaDB 3307，不是部署默认端口。

## 官方体育数据

已核验快照位于 data/official/20261021，包含来源 URL、时间、原始响应与哈希。离线重放：

```powershell
php yii fifa/import data/official/20261021
```

重新联网采集：

```powershell
python -m pip install -r scripts/requirements-fifa.txt
powershell -File scripts/sync_official.ps1 -Python python -Php php
```

仅允许 FIFA 官方 HTTPS 源，有限重试、限速和断点缓存；完整 manifest 验证通过才入库。采集比赛接口与官方名单 PDF，不生成缺失球员指标。重新导入保留实体 ID 与用户关联。`official_import_id` 关联原始证据；人工修改核心事实会取消该记录的官方核验标记。

## 新闻与视频

```powershell
php yii news/sync
powershell -File scripts/start_news_worker.ps1
php yii video/seed
```

新闻源固定 HTTPS 主机白名单、响应上限、超时、短摘要和原始来源；worker 每 300 秒轮询，可在本机进程日志查看同步结果。Linux 可由进程管理器运行 `php yii news/watch 300`。页面每分钟检查状态，有新增时给出刷新入口，不宣称瞬时推送。英文来源保留原标题，不伪造中文译文。

视频仅 2026 年；发布时校验官方元数据。RTVE 的完整决赛需要官方账户和 DRM，嵌入的是公开播放器文档，不保存、代理或拆解受保护媒体流。现有证据证明元数据和 iframe 接入，未证明登录后流播放成功。

## 备份、回滚与重新升级

部署前保留完整代码版本、公开原始证据及数据库全量备份。使用数据库工具的凭据交互／受保护配置，不把密码写到 shell 参数或仓库。

```powershell
# 只在已备份、明确选中的库上执行
php yii migrate/down 5 --interactive=0
php yii migrate/up --interactive=0
```

down 保留原始 34 表、原有体育记录与密码哈希；会移除新增模块表／列，因此论坛、通知、核验字段、视频等新增数据必须从全量备份恢复，不能靠 migrate/up 找回。重新升级后可重放官方快照恢复核验关系。原始模式加迁移历史为 35 表，升级后为 48 表。

测试恢复使用明确隔离的数据库名，脚本拒绝其他前缀：

```powershell
$env:DB_DATABASE='wcu_restore_example'
php tests/schema_roundtrip.php
Remove-Item Env:DB_DATABASE
```

UI／配置回滚演示只还原独立副本，不覆盖运行中的改好文件。命令和 SHA256 见 artifacts/VERIFICATION.txt。

2026-10-22 浏览器实测：官方 iframe 标题和封面已加载；点击播放后，RTVE 返回所在地播放权限制（“Este contenido tiene restringidos los derechos de emisión en su ubicación”）。本站显示官方登录／地区限制提示，未播放成功，不绕过地区或 DRM 校验。
