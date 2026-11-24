# WorldCup Universe / 世界杯宇宙

Yii2 原生 MVC + MySQL 的世界杯课程项目。当前开发包含赛事/球队/球员/国家探索、新闻、管理台，以及球迷论坛、主队选择、回复通知、点赞收藏与举报审核。

## 快速启动

需要 PHP 8.1+（pdo_mysql、mbstring、curl、SimpleXML、fileinfo）、Composer、MariaDB 10.4+（`data/install.sql` 在 VARCHAR(800/900) 上的唯一索引依赖 MariaDB 的长唯一键，MySQL 8 会报 1071 无法建表）。

```powershell
Copy-Item .env.example .env
# 编辑 .env 的 DB_* 和 ADMIN_PASSWORD（至少 12 位，不提交到 Git）
composer install --no-dev --prefer-dist
php yii app/install
php -S 127.0.0.1:8080 -t public public/index.php
```

## 部署

完整说明见 `data/team/` 下的《部署文档》PDF（架构、公网部署、自动更新、本地一键部署、备份恢复、故障处理）。

- **正式网站**：https://worldcup-universe.dynv6.net/ （阿里云 ECS，Nginx + PHP-FPM + MariaDB，HTTPS；GitHub `main` 每 15 分钟自动安全更新）
- **Windows 本地一键部署**：安装 XAMPP 8.2（PHP 8.1+、MariaDB 10.4+）后双击 `deploy-local.bat`，完成后访问 http://127.0.0.1:8080/ ；停止用 `stop-local.bat`。重复运行不会覆盖已有数据。
- **备份**：服务器每日本机备份，并加密推送到 GitHub 私有仓库；`fetch-backup.bat` / `restore-local.bat` 在 Windows 上下载、验证和恢复。
- **公网服务器部署/更新**：`sudo bash scripts/deploy.sh --install-packages --domain <域名> --email <邮箱>`；状态检查 `sudo bash scripts/status.sh`。

本地前台 `http://127.0.0.1:8080`，管理台 `http://127.0.0.1:8080/admin`；管理员由 `.env` 初始化（本地部署脚本随机生成初始密码），没有写死在前端。

后台“媒体资产、项目团队、球员、球队、新闻、课程交付”支持浏览器上传、预览与替换（`services/MediaUploadService.php`）；公开媒体保存在 `public/uploads/`，课程作业保存在 `data/<team|personal>/uploads/`，仅在记录“前台可见”时可下载。

## 原生架构

```text
controllers/  Yii2 Controller / AccessControl / CSRF
models/       原有 34 表及论坛扩展的 ActiveRecord / rules / relations
services/     查询聚合、事务、所有权校验、RSS同步、审计
views/        Yii2 render，前后台独立 layout
commands/     Yii2 Console 安装、审计、新闻同步
migrations/   增量数据库升级与 down
public/       唯一 Web 根目录
 data/install.sql       基础 schema
 data/sports-seed.sql   公开体育快照，不含用户/密钥/论坛测试内容
 data/team/             团队交付
 data/personal/         个人交付
```

网页请求全部走 Yii2 MVC。`app/` 只保留命令行采集脚本共用的数据库连接、HTTP 客户端和辅助函数（`app/bootstrap.php`），不承担页面业务。

## 主要流程

- 主队：一人最多一支，关注球队/球员可多选，不与屏蔽球队重叠。
- 论坛：板块/球队筛选、主队看台、主题与回复、同主题引用、编辑/删除、幂等点赞/收藏、回复通知、举报与管理员审核。
- 权限：只能修改自己的内容；管理员可以隐藏/恢复、锁定/解锁、置顶、处理举报；所有写操作校验 CSRF。
- 新闻：保留来源、原文链接和发布时间，草稿不公开；原生 RSS 同步：`php yii news/sync`，生产环境由 `wcu-news-sync.timer` 每十分钟执行。
- 赛事：2026 年一届，FIFA 官方名单 PDF 与比赛接口共 48 队、1,248 名球员、104 场比赛、5,323 条阵容记录、32 条已核验晋级关系。原始响应、时间与 SHA256 随仓库保存；比分是采集快照，不宣称实时推送。
- 导航：赛事内统一提供赛程与晋级、小组积分、比赛回放；小组赛与积分按 A–L 组折叠。旧 `/stats` 永久跳转至 `/worldcup/standings`，不再重复开设数据栏目。
- 新闻：RSS 同步保留原文标题、短摘要、配图和原文链接；每轮至少有一个来源同步成功即完成本轮，失败来源记录错误并在下一轮重试。
- 页面：Arena、Tabler、Multiverse 组件及本地中文字体；各模块不同窄幅首图，球队相关内容显示国旗。56 张官方球员肖像已缓存，足球鼠标使用真实透明贴图。
- 视频：`/watch` 仅嵌入央视网可直接打开的 2026 世界杯页面，页面内切换播放源；不再保留 YouTube、RTVE 或其他无法在站内打开的外部播放器。
- 媒体：真实场馆／城市摄影，作者与许可见 `/credits`；原图保留，部分展示使用 MediaWiki 官方 1280px 预览。

## 验证

```powershell
php tests/backend.php
php tests/official.php
php tests/news.php
php tests/site_health.php
python tests/http_acceptance.py
python tests/upload_acceptance.py
python tests/chinese_ui.py
python tests/flag_ui.py
python tests/navigation_ui.py
python tests/redesign_ui.py
python tests/team_photo_ui.py
python tests/topic_replies.py
node --test tests/globe-data.test.mjs
php yii app/audit
```

数据库测试会回滚写入；HTTP 测试会生成带 `qa_` 标记的临时账号。**使用专用测试数据库**，并按输出的精确前缀清理：`php tests/cleanup.php qa_XXXXXXXX`。测试服务器默认 `http://127.0.0.1:8088`，可用 `WCU_TEST_URL` 环境变量设置（`*_ui.py` 固定使用 8088）。`redesign_ui.py` 与 `topic_replies.py` 从空白论坛开始创建并清理各自的测试数据；`topic_replies.py` 优先读取 `ADMIN_USERNAME`/`ADMIN_PASSWORD` 环境变量。`tests/upload_acceptance.py` 默认 `http://127.0.0.1:8098`，需要 `ADMIN_PASSWORD` 环境变量，并在项目根目录运行（会检查并清理自己上传的文件）。

## 课程交付

- 团队作业（需求、设计、实现、用户手册、部署文档等）放在 `data/team/`，个人作业放在 `data/personal/`；网站“项目团队”页（`/team`）提供下载链接，并列出成员分工与每人负责的 MVC 文件。
- 数据库建表脚本：`data/install.sql`；初始赛事数据：`data/sports-seed.sql`。
- 每个源码文件开头都有中文注释头，说明文件作用；按分工能确定作者的文件注明了“姓名_学号”。

| 成员 | 学号 | 负责内容 |
|---|---|---|
| 蒋林瀞（队长） | 2413507 | 项目统筹与进度；后台管理与内容审核；球迷论坛；界面重构与动效；服务器部署、自动更新与备份 |
| 王麒萱 | 2412096 | 界面原型与视觉规范；账号注册登录；个人中心与回复通知；数据库全套设计 |
| 崔迪 | 2412439 | 前台信息架构与导航；首页、球队与球员页面；国家探索与 3D 地球 |
| 梁靖泽 | 2412852 | 赛事内容模块；官方数据采集与导入；赛程、积分与晋级对阵图；新闻同步 |
