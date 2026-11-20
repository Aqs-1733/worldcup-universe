# 互联网数据库世界杯网站实现文档

## 1 项目内容

世界杯宇宙使用 PHP 8.2、Yii2 和 MySQL/MariaDB 编写。网站围绕 2026 世界杯资料组织前台页面和后台维护页面。前台提供赛事、球队、球员、新闻、国家资料、场馆、视频和球迷论坛；后台维护赛事资料、新闻、论坛内容和用户状态。

### 1.1 前台页面

球队页面列出 48 支参赛队伍，卡片显示国旗和队伍照片。进入详情页可以阅读阵容、赛程和球队资料。球员页面保存号码、位置、出生日期、俱乐部、身高和国家队数据，并关联所属队伍。

赛事页面保存 104 场比赛，按小组赛和淘汰赛阶段显示。比赛卡片和淘汰赛图同时显示双方国旗、比分、开球时间和比赛阶段。积分页面从 standings 表读取各队场次、胜平负、净胜球和积分。国家页面保存参赛国家的首都、语言、货币和坐标，场馆页面保存举办城市和场馆介绍。

新闻页面从 RSS 或 Atom 来源写入新闻标题、摘要、原文链接、发布时间和标签。页面保留新闻来源链接。视频页面使用 CCTV 官方视频地址，页面内嵌播放组件。个人中心支持注册用户选择主队、关注球队和球员。

球迷论坛提供发主题、回复、点赞、收藏和举报功能。发布和回复需要登录。论坛初始数据不放置示例用户和示例讨论，数据库保留管理员账号，主题和回复由注册用户发布。

### 1.2 后台页面

后台入口为 /admin。管理员可以维护球队、球员、比赛、新闻、标签、用户和论坛审核记录。后台修改新闻标签、处理举报、调整用户状态和删除内容时写入 audit_logs。

## 2 数据采集和数据库

### 2.1 世界杯资料采集

使用 scripts/fetch_fifa.py 读取 FIFA 公开接口和官方名单 PDF。脚本只访问 api.fifa.com 和 fdp.fifa.org 两个 HTTPS 域名。下载内容同时保存原始文件、请求地址、下载时间、HTTP 状态和 SHA256。名单 PDF 包含 48 页，每页对应一支队伍；每队保存 26 名球员。赛程数据保存 104 场比赛和 104 份比赛详情。

采集文件保存到 data/official/20261021。目录中的 normalized.json 保存整理后的球队、球员、比赛和阵容数据，manifest.json 保存数量和哈希。导入前读取 manifest.json，比较 normalized.json 与各来源文件的 SHA256，并限制来源域名。

### 2.2 数据导入

执行 php yii fifa/import data/official/20261021 导入官方快照。服务在数据库事务中写入 teams、players、matches、standings、match_lineups、match_stats、match_events 和 player_statistics。导入前清除这些表中旧的 official_import_id，再写入新的 official_import_id 和 source_synced_at。事务提交后刷新缓存。

导入结果保存 48 支球队、1,248 名球员、104 场比赛、5,323 条官方阵容和 32 条已核验晋级关系。球员照片地址来自官方比赛详情，球队国旗地址和参赛国家资料一同写入数据库。

### 2.3 新闻数据

news_sources 保存新闻来源名称、首页地址、RSS 地址、语言和启用状态。php yii news/sync 读取启用来源，过滤不安全链接，保存新闻标题、摘要、来源链接和发布时间。中国新闻网体育 RSS 已写入新闻来源表。同步结果写入 news_articles 和 news_article_tags，页面按发布时间和标签读取记录。

### 2.4 数据库结构

基础建表语句位于 data/install.sql，migrations 目录保存增量迁移。数据库有 48 张表。teams、players、matches、standings 和 match_lineups 保存赛事主体数据；country_profiles、venues 和 media_assets 保存国家、场馆和媒体资料；users、roles、user_roles、fan_profiles 和 fan_preferences 保存账号和个人偏好；forum_threads、forum_replies、thread_likes、thread_bookmarks 和 forum_reports 保存论坛数据；news_sources、news_articles、tags 和 news_article_tags 保存新闻数据。

执行 php yii app/install 时，程序导入基础表结构并执行 Yii2 迁移。已有赛事记录时保留原有球队、球员和比赛数据。角色表写入管理员和普通用户两个角色，标签表写入比赛内容、战术分析、球队动态、球迷内容、娱乐内容和二创内容。

## 3 程序结构

### 3.1 目录安排

config 保存应用配置、数据库连接和路由规则。controllers 保存前台和后台控制器。models 保存 ActiveRecord 模型和字段规则。services 保存官方数据导入、新闻同步、论坛权限、审计和媒体路径处理。views 保存前台、后台和公共布局。commands 保存 Yii2 控制台命令。tests 保存数据库、官方数据和 HTTP 测试。public/index.php 作为 Web 入口加载 Composer、Yii2 和应用配置。

### 3.2 MVC 模块

球队资料模块使用 TeamController、Team、Player、MatchGame 和 CatalogService，视图位于 views/catalog/teams.php 和 views/catalog/team.php。赛事模块使用 MatchController、MatchGame、Standing、MatchProgression 和 CatalogService，页面包括赛程、比赛详情、积分和淘汰赛图。

论坛模块使用 ForumController、ForumThread、ForumReply 和 ForumService。控制器接收请求并返回页面，服务层处理主题、回复、点赞、收藏、举报和审核。后台模块使用 AdminController、AuditService 和 ActiveRecord 模型，后台视图位于 views/admin-v2。

## 4 主要实现

### 4.1 官方快照导入

控制台命令接收快照目录，调用 OfficialFifaSyncService。服务返回的报告写入命令行，报告中包含球队、球员、比赛、阵容和晋级关系数量。

```php
public function actionImport(string $folder): int
{
    $report = (new OfficialFifaSyncService())->import($folder);
    $this->stdout('PASS FIFA import: '
        . json_encode($report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
    return 0;
}
```

OfficialFifaSyncService 读取 manifest.json 后，比较队伍数、球员数、比赛数和来源文件哈希。服务使用 beginTransaction 开启事务，写入结束后提交事务。

### 4.2 论坛回复

ForumController 的回复入口只接受 POST 请求。控制器把登录用户、主题编号和表单内容交给 ForumService。服务层验证回复内容、主题状态和父回复关系。验证错误时页面返回 422 状态；写入成功后跳转到新回复位置。

```php
public function actionReply(int $id)
{
    $this->posted();
    $reply = (new ForumService())->reply($this->actor(), $id, Yii::$app->request->post());
    if ($reply->hasErrors()) {
        Yii::$app->response->statusCode = 422;
        return $this->showThread((new ForumService())->readable($id, $this->actor()), $reply);
    }
    return $this->redirect('/forum/' . $id . '#reply-' . $reply->id);
}
```

论坛服务把作者编号写入回复记录，不接收表单中的 author_id。编辑和删除主题时比较登录用户与主题作者，管理员拥有审核权限。举报记录关联提交用户、主题和处理结果。

### 4.3 新闻同步

NewsController 的 sync 命令调用 NewsImportService。服务读取每个启用来源，解析 RSS 或 Atom 条目，删除 HTML 标签并过滤 script、data 和 protocol-relative 链接。同步记录保存来源名称、发布时间、原文链接和标签。

### 4.4 安装和权限

AppController 的 install 命令读取 data/install.sql，使用 SqlFileService 分割 SQL 语句并执行建表。命令随后执行 Yii2 迁移，创建角色和默认标签。注册接口创建 User 和 FanProfile，普通注册账号不写入管理员角色。登录状态使用 HttpOnly 和 SameSite=Lax Cookie，提交表单使用 CSRF 校验。

## 5 团队协作记录

### 5.1 分工

蒋林瀞负责统筹和后台管理模块。崔迪负责前台信息架构。梁靖泽负责赛事内容模块。王麒萱负责界面原型和实现文档。

### 5.2 Git 记录

仓库使用 Git 管理代码。2026 年 10 月 22 日的 f1eb216 建立 Yii2 和 MySQL 的世界杯数据项目。3230fc8 补充 2026 队伍合照和论坛回复界面。0fcd233 加入首页、淘汰赛图、地球叙事和本地视频栏目。b4560a1 调整亮色页面并加入球员动作交互。f662c11 修正淘汰赛连接线、新闻采集 SSL 和球队卡片。

2026 年 11 月的 0730a13 替换无法播放的远程视频来源。7766f3e 增加站点健康测试并整理社区数据。4348c69 删除论坛中的生成式示例账号、帖子和回复。Git 日志保存每次提交的署名、时间、说明和改动文件。

## 6 运行结果

网站启动后，首页可以进入赛事、球队、球员、新闻、国家资料、视频和论坛页面。球队与球员页面显示对应资料，赛事页面显示赛程、积分和淘汰赛图，国家和场馆页面显示参赛国家及举办地信息。

注册用户可以选择主队、关注球队和球员，也可以在论坛发布主题、回复讨论、点赞、收藏和举报。管理员在后台维护赛事资料和新闻内容，处理论坛举报和用户状态。新闻页面显示同步后的文章及来源链接，视频页面使用 CCTV 官方视频地址播放内容。
