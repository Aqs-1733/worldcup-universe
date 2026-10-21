# WorldCup Universe / 世界杯宇宙

Yii2 原生 MVC + MySQL 的世界杯课程项目。当前开发包含赛事/球队/球员/国家探索、新闻、管理台，以及球迷论坛、主队选择、回复通知、点赞收藏与举报审核。

## 快速启动

需要 PHP 8.1+（pdo_mysql、mbstring、curl、SimpleXML、fileinfo）、Composer、MySQL 8.0+ 或兼容 MariaDB/InnoDB。

```powershell
Copy-Item .env.example .env
# 编辑 .env 的 DB_* 和 ADMIN_PASSWORD（至少 12 位，不提交到 Git）
composer install --no-dev --prefer-dist
php yii app/install
php -S 127.0.0.1:8080 -t public public/index.php
```

Windows 可运行 `setup.ps1` 完成环境检查、依赖安装和建库迁移，再运行 `start_php.bat`。安装命令保持已有账号/密码和体育记录不变；全新空库才导入随仓库提供的公开体育快照。数据库账号需要建库、建表和迁移权限；生产环境完成部署后可使用权限更小的运行账号。

前台：`http://127.0.0.1:8080`，管理台：`http://127.0.0.1:8080/admin`。管理员从本地 `.env` 初始化，没有写死在前端。

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

核心请求不再调用 LegacyBridgeController / 自定义 PDO Repository / 自定义 View。历史采集适配工具保留在 `scripts/` 和 `app/`，不承担当前页面核心业务。

## 主要流程

- 主队：一人最多一支，关注球队/球员可多选，不与屏蔽球队重叠。
- 论坛：板块/球队筛选、主队看台、主题与回复、同主题引用、编辑/删除、幂等点赞/收藏、回复通知、举报与管理员审核。
- 权限：只能修改自己的内容；管理员可以隐藏/恢复、锁定/解锁、置顶、处理举报；所有写操作校验 CSRF。
- 新闻：保留来源、原文链接和发布时间，草稿不公开；原生 RSS 同步：`php yii news/sync`。
- 赛事：2026 年一届，FIFA 官方名单 PDF 与比赛接口共 48 队、1,248 名球员、104 场比赛、5,323 条阵容记录、32 条已核验晋级关系。原始响应、时间与 SHA256 随仓库保存；比分是采集快照，不宣称实时推送。
- 导航：赛事内统一提供赛程与晋级、小组积分、比赛回放；小组赛与积分按 A–L 组折叠。旧 `/stats` 永久跳转至 `/worldcup/standings`，不再重复开设数据栏目。
- 新闻：7 个真实 RSS 来源，按类型和来源筛选，后台每 300 秒轮询；保留原文标题、短摘要、配图和原文链接，不把比赛结果自动拼成新闻。`powershell -File scripts/start_news_worker.ps1` 启动采集进程。
- 页面：Arena、Tabler、Multiverse 组件及本地中文字体；各模块不同窄幅首图，球队相关内容显示国旗。56 张官方球员肖像已缓存，足球鼠标使用真实透明贴图。
- 视频：`/watch` 收录经官方元数据核验的 2026 决赛完整回放，嵌入 RTVE 官方播放器；需官方账号登录，未验证登录后的受保护视频流。没有虚构 104 场可播放地址。
- 媒体：真实场馆／城市摄影，作者与许可见 `/credits`；原图保留，部分展示使用 MediaWiki 官方 1280px 预览。
- AI：文本/生图服务需配置自己的凭据；私有图片上传已实现，视觉模型分析尚未接入，不返回虚构结果。

## 验证

```powershell
php tests/backend.php
php tests/official.php
php tests/news.php
python tests/http_acceptance.py
python tests/chinese_ui.py
python tests/flag_ui.py
python tests/navigation_ui.py
node --test tests/globe-data.test.mjs
php yii app/audit
```

数据库测试会回滚写入；HTTP 测试会生成带 `qa_` 标记的临时账号。**使用专用测试数据库**，并按输出的精确前缀清理：`php tests/cleanup.php qa_XXXXXXXX`。测试服务器默认 `http://127.0.0.1:8088`，可用 `WCU_TEST_URL` 环境变量设置。

## 设计与交付

- `docs/v2-architecture.md`：需求、架构、模块/流程/ER 图、权限、页面与数据边界。
- `docs/v2-database-dictionary.md`：实际迁移后数据库完整字典。
- `docs/v2-api.md`：接口、状态码、CSRF 与请求示例。
- `docs/v2-deployment.md`：全新安装、已有库升级、恢复与回滚。
- `docs/v2-coursework.md`：逐条对照老师课件与设计方案，区分代码完成和最终课程材料。
- `docs/v2-acceptance.md`、`artifacts/VERIFICATION.txt`：真实测试和回滚证据。

4 位成员资料已按原个人作业核对。每人的实际贡献签认、最终 PDF/PPT 与本人录屏仍需本人确认补齐；交付清单逐项标注，不把代码说明冒充这些材料。

2026-10-22 浏览器实测：官方 iframe 标题和封面已加载；点击播放后，RTVE 返回所在地播放权限制（“Este contenido tiene restringidos los derechos de emisión en su ubicación”）。本站显示官方登录／地区限制提示，未播放成功，不绕过地区或 DRM 校验。

## 2026-10-22 截图修订

- 论坛：采用 Tabler Card / List group / Avatar / Chat 组合，紧凑主题列表、板块卡片和主队侧栏。运行 `php yii design/forum` 可幂等添加 6 个明确标注的示例主题、2 条示例回复；均由现有管理员发布，不创建虚构球迷账号、点赞或热度。
- 晋级图：改成原生 details 逐轮展开的来源树，32 场唯一节点、30 条胜者树边；季军赛独立展示，不重复整棵半决赛子树。可按队展开路线，无固定宽画布或双向内部滚动。
- 球队封面：撤除个人肖像，接入 GER/NOR/COL/ENG/BRA 五队经核验的官方 2026 合照，其余 43 队为纯国旗设计。巴西原站水印原样保留。清晰、无大水印的合照优先展示。
- 国家：48 张语义链接卡片、前端筛选／搜索、`/countries/{country_code}` 原生详情；中文展示标签不修改原始国家资料。
- 留言：Tabler card/chat 形式，保留登录、CSRF、提交审核流程；页脚与页面／导航统一最大宽度及内边距。
- 本轮验收与截图：`artifacts/redesign-20261022/VERIFICATION.txt`；页面回归：`python tests/redesign_ui.py`。
