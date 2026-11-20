# 世界杯宇宙 实现文档

2026 世界杯资料与球迷社区课程团队项目

代码版本：`main` 分支 `4348c69`

团队成员：崔迪 2412439    梁靖泽 2412852    蒋林瀞 2413507    王麒萱 2412096

撰写人：王麒萱（2412096）

## 1 项目实现概述

系统名称为世界杯宇宙，使用 PHP、Yii2 和 MySQL/MariaDB 实现。前台包括赛事、球队、球员、新闻、国家探索、视频和球迷论坛；后台包含赛事资料、新闻、留言、课程材料和审核记录的维护入口。文档所列代码以 main 分支 4348c69 为准，运行数据记录于 2026 年 11 月 20 日。

文档依据仓库源码、data/official/20261021 保存的原始快照、Git 提交记录和运行结果编写，内容包括数据采集、数据入库、Yii2 MVC 页面、用户交互、后台管理和测试。

| 项目项 | 实现内容 |
| --- | --- |
| 技术栈 | PHP 8.2、Yii2、MySQL/MariaDB、原生 JavaScript、CSS |
| 应用入口 | public/index.php |
| 数据库入口 | data/install.sql，增量调整放在 migrations/ |
| 团队材料目录 | data/team/ |
| 个人材料目录 | data/personal/ |
| 版本控制 | GitHub 仓库 Aqs-1733/worldcup-universe，main 分支 |

## 2 开发过程

### 2.1 代码仓库和目录约定

项目将公开页面、后台页面、数据脚本和课程材料放在不同目录。Web 服务的根目录是 public，控制器、模型、服务和视图按 Yii2 的目录习惯组织。课程要求中的 SQL 文件放在 data/install.sql，团队交付和个人交付分别放在 data/team/ 与 data/personal/。

| 目录 | 内容 |
| --- | --- |
| config/ | 应用配置、路由、数据库连接和后台可维护表的白名单。 |
| controllers/ | 请求入口，负责参数读取、登录状态和页面跳转。 |
| models/ | ActiveRecord 模型、表字段规则和关联关系。 |
| services/ | 赛事导入、论坛权限、新闻同步、审计和素材校验等业务处理。 |
| views/ | 前台、后台和公共布局模板。 |
| commands/ | Yii2 控制台命令，例如 FIFA 快照导入和新闻同步。 |
| migrations/ | 在基础表结构之外执行的增量变更。 |
| tests/ | 数据库、官方数据、HTTP、导航和界面回归检查。 |

### 2.2 数据采集和快照保存

赛事资料通过 scripts/fetch_fifa.py 从 FIFA 公开接口和官方名单 PDF 获取。脚本只接受 api.fifa.com 与 fdp.fifa.org 两个 HTTPS 域名，下载后同时保存响应文件、元数据和 SHA256。名单 PDF 按 48 页解析，每支队伍要求得到 26 名球员；赛程要求覆盖 104 场比赛。任一数量、球队编号或哈希不符合预期时，脚本停止生成导入快照。

采集完成后，data/official/20261021/ 保存 normalized.json、manifest.json、比赛详情和名单来源文件。命令 php yii fifa/import data/official/20261021 会先核验 manifest 中的哈希和来源域名，再在一个数据库事务中写入球队、球员、比赛、积分、阵容和晋级关系。导入服务把旧的官方标记置空，再写入导入标记，最后提交事务并刷新缓存。

**代码 1 赛事导入命令 commands/FifaController.php**

```php
public function actionImport(string $folder): int
{
    $report = (new OfficialFifaSyncService())->import($folder);
    $this->stdout('PASS FIFA import: '
        . json_encode($report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
    return 0;
}
```

新闻模块从 news_sources 表读取 RSS 或 Atom 来源，由 php yii news/sync 同步。新闻记录保存来源名称、原文链接、发布时间、摘要和标签；页面展示时仍保留来源链接。

### 2.3 数据库初始化和增量修改

data/install.sql 用于建立基础库，migrations/ 保留后续的结构调整。项目运行时使用 ActiveRecord 访问数据表，业务修改放在数据库事务中。审计命令统计到 48 张表，表数量符合课程设计要求。

| 数据范围 | 审计结果 2026-11-20 |
| --- | --- |
| 参赛球队 | 48 |
| 球员 | 1,248 |
| 比赛 | 104 |
| 已核验晋级关系 | 32 |
| 国家资料 | 48 |
| 新闻来源 | 8 |
| 新闻文章 | 39 |
| 论坛主题和回复 | 0 个主题、0 条回复 |

### 2.4 Yii2 请求和页面实现

public/index.php 只负责加载 Composer、Yii2 和应用配置。config/web.php 设置 URL 规则，将 /teams、/players、/worldcup、/news、/forum、/countries、/watch 和 /admin 等路径映射到对应控制器。请求对象开启 CSRF 校验；会话 Cookie 采用 HttpOnly 与 SameSite=Lax 配置。

**代码 2 应用入口 public/index.php**

```php
require $vendorAutoload;
require $yiiBootstrap;
require dirname(__DIR__) . '/app/bootstrap.php';
header('X-Backend: Yii2');

$config = require dirname(__DIR__) . '/config/web.php';
(new yii\web\Application($config))->run();
```

球队页面由 TeamController 读取 CatalogService 返回的数据提供器，详情页同时查询该球队的球员、比赛和已审核留言。控制器只组织请求参数和视图数据，查询和业务规则放在模型与服务层。

**代码 3 球队列表控制器 controllers/TeamController.php**

```php
public function actionIndex()
{
    return $this->render('/catalog/teams', [
        'provider' => (new CatalogService())->teams(Yii::$app->request->get())
    ]);
}
```

论坛模块由 ForumController 和 ForumService 共同处理。未登录用户可以浏览公开主题，发布、回复、点赞、收藏和举报经过 AccessControl、POST 校验和 CSRF 校验。回复、主题删除、举报和审核在服务层继续检查登录用户与内容所有者的关系。

**代码 4 回复入口 controllers/ForumController.php**

```php
public function actionReply(int $id)
{
    $this->posted();
    $reply = (new ForumService())->reply(
        $this->actor(), $id, Yii::$app->request->post()
    );
    if ($reply->hasErrors()) {
        Yii::$app->response->statusCode = 422;
        return $this->showThread((new ForumService())->readable($id, $this->actor()), $reply);
    }
    return $this->redirect('/forum/' . $id . '#reply-' . $reply->id);
}
```

### 2.5 后台、文件和审计记录

后台入口为 /admin。AdminController 对可维护表使用 config/v2-tables.php 中的白名单，普通用户访问后台会被权限过滤器拦截。删除、新闻标签修改、用户状态调整和审核操作会写入 audit_logs。课程文件的下载路径经过模型校验，文件范围限制在 data/team/ 和 data/personal/。

## 3 MVC 对应关系

系统包含球队资料、赛事与积分、球迷论坛和后台维护四组 MVC 模块。每组模块均由控制器接收请求、模型或服务处理数据、视图输出页面。

| 功能 | Controller | Model 或 Service | View |
| --- | --- | --- | --- |
| 球队资料 | TeamController | Team、Player、MatchGame、CatalogService | views/catalog/teams.php、team.php |
| 赛事与积分 | MatchController | MatchGame、Standing、MatchProgression、CatalogService | views/catalog/matches.php、match.php、stats.php |
| 球迷论坛 | ForumController | ForumThread、ForumReply、ForumService | views/forum/index.php、view.php、edit.php |
| 后台维护 | AdminController | ActiveRecord 模型、AuditService、ForumService | views/admin-v2/index.php、table.php、edit.php |

### 3.1 团队分工

| 成员 | 主要负责内容 |
| --- | --- |
| 蒋林瀞 2413507 | 统筹与后台管理模块 |
| 崔迪 2412439 | 前台信息架构 |
| 梁靖泽 2412852 | 赛事内容模块 |
| 王麒萱 2412096 | 界面原型与实现文档 |

## 4 Git 协作记录

项目使用 Git 管理版本。2026 年 11 月 20 日查看 main 分支，代码版本为 4348c69。下表摘自仓库 Git 日志。

| 日期 | 提交 | Git 署名 | 提交内容 |
| --- | --- | --- | --- |
| 2026-10-22 | f1eb216 | Aqs-1733 | 重建 Yii2 和 MySQL 的世界杯数据项目。 |
| 2026-10-22 | 3230fc8 | Aqs-1733 | 补充 2026 队伍合照和论坛回复界面。 |
| 2026-10-24 | 0fcd233 | Skylar-Jiang | 加入首页、淘汰赛图、地球叙事和本地视频栏目。 |
| 2026-10-24 | b4560a1 | changluei | 统一亮色页面并加入球员动作交互。 |
| 2026-10-28 | f662c11 | Skylar-Jiang | 修正淘汰赛连接线、新闻采集 SSL 和球队卡片。 |
| 2026-10-31 | 1e46d90 | Skylar-Jiang | 加入跨页面视图过渡。 |
| 2026-11-18 | 0730a13 | Aqs-1733 | 替换无法播放的视频来源。 |
| 2026-11-18 | 7766f3e | Aqs-1733 | 补充站点健康检查并整理社区数据。 |
| 2026-11-19 | 4348c69 | Aqs-1733 | 清除论坛中的生成式示例内容。 |

Git 记录保留了代码修改的时间、署名和说明。使用 git log --oneline --decorate 与 git show <提交号> 可以查看修改的文件。

## 5 运行结果和检查

运行时使用 D:/XAMPP/php/php.exe 执行数据库和官方数据检查，数据库端口为 3310。后端测试在事务中创建临时记录，结束后回滚。下表记录 2026 年 11 月 20 日的输出。

| 检查项 | 执行命令 | 结果 |
| --- | --- | --- |
| 后端业务与权限 | php tests/backend.php | 47 项通过，测试写入已回滚。 |
| FIFA 快照 | php tests/official.php | 3,048 项通过；48 队、1,248 球员、104 场、5,323 条官方阵容、32 条晋级边。 |
| 站点健康检查 | php tests/site_health.php | 15 项通过，检查视频、新闻、论坛、国家资料和场馆资料。 |
| 数据库审计 | php yii app/audit | 返回 48 张表及球队、球员、比赛、新闻、论坛等数据计数。 |

官方数据检查会核对 data/official/20261021/manifest.json 中的 SHA256、来源域名、球队和球员字段、比赛字段、阵容数量和晋级关系。后端测试覆盖注册、主队选择、论坛所有权、举报审核、媒体路径、课程文件下载和 SQL 解析等流程。

新闻文章和比赛比分来自同步数据或快照。新闻数量随同步结果变化，比赛结果按采集快照展示。
