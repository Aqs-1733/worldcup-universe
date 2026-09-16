# 老师要求映射与提交清单

本文件只记录老师给出的阶段要求和当前项目对应位置，便于后续按个人学号、姓名补截图并打包提交。项目本体仍放在 `D:\worldcup-ai-universe`，不混入 `D:\smartcane`。

## 个人作业

| 阶段 | 老师要求 | 当前留存 | 状态 |
| --- | --- | --- | --- |
| 个人作业1：Web 前端初探 | 对任意网页调研不同请求方式，至少 GET/POST；写出或截图请求及响应包；使用 JQuery 触发一个事件，写至少三句话并截图前后状态；完成一个浏览器插件并在文档中说明功能及代码。 | `docs/coursework/personal-1-web-request-jquery-plugin.md` | 待个人截图补齐 |
| 个人作业2：Web 前端设计 | 使用 Axure 等软件设计某个页面；为团队选取前台及后台模板；提交文档。 | `docs/coursework/personal-2-frontend-design.md` | 待个人原型截图补齐 |
| 个人作业3：开源建站工具初试 | 自行安装 WordPress 并做简单页面改造、设计布局；记录安装步骤和最终页面。 | `docs/coursework/personal-3-wordpress-trial.md` | 待个人实验截图补齐 |

## 团队作业

| 阶段 | 老师要求 | 项目对应 |
| --- | --- | --- |
| 团队作业1：需求文档 | 体育主题 2026 世界杯、队名、前后台功能、数据库 10+ 表、动态图形加分 | `docs/requirements.md`、`/coursework`、`database/schema.sql` |
| 团队作业2：设计文档 | 前后台页面、模块、数据库、页面流程 | `docs/design.md` |
| 团队作业3：实现文档 | 记录主要代码实现和模块 | `docs/implementation.md` |
| 团队作业4：用户手册 | 普通用户和管理员操作说明 | `docs/user-manual.md` |
| 团队作业5：部署文档 | 部署环境、部署流程、成功截图 | `docs/deployment.md` |
| 团队作业6：项目展示 | PPT：项目展示、分工、数据库设计、代码、文档分工、工作量分数 | `docs/presentation-outline.md` |

## 项目本体对要求的满足情况

- 队名：`worldcup-ai-universe`。
- 技术栈：PHP + MySQL，不使用 SQLite。
- 入口：`public/index.php`，自定义 MVC 结构。
- 前台：`/`、`/worldcup`、`/teams`、`/players`、`/news`、`/fan-space`、`/ai-studio`、`/vision`、`/coursework`。
- 后台：`/admin`，可维护团队成员、球队、球员、赛程、新闻、发布内容、留言、数据来源、校验结果和课程交付清单。
- 数据库：`database/schema.sql` 中含 30+ 张表，满足大于 10 张表要求。
- 动态图形：首页积分条形图、世界杯对战图、课程交付时间线、滚动新闻和页面动效。
- 协作：GitHub 仓库 `https://github.com/Aqs-1733/worldcup-ai-universe.git`，当前开发分支 `codex/php-mysql-rebuild`。

## 最终提交建议

- 个人文档按老师格式单独打包：`作业1(学号_姓名).zip`、`作业2(学号_姓名).zip`、`作业3(学号_姓名).zip`。
- 团队文档建议统一用队名开头，例如：`worldcup-ai-universe_需求文档(学号1_学号2_学号3).pdf`。
- 最终项目包建议包含：源码、数据库 SQL、部署文档、用户手册、项目展示 PPT、截图证明、GitHub 链接。
