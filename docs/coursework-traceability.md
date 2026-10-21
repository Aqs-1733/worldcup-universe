# 老师要求映射与提交清单

本文件记录老师阶段要求与当前实现位置，便于按真实学号、姓名补材料并打包。当前开发项目为 `D:\Project114514\worldcup-universe`，分支为 `codex/yii2-full-stack`；不与父目录的其他项目混用。团队与个人正式材料分别放入 `data/team/`、`data/personal/`，经后台登记后从 `/deliverables` 下载。说明文件和开发提纲不是最终课程提交件；完整状态见 `docs/v2-coursework.md`。

## 个人作业

| 阶段 | 老师要求 | 当前留存 | 状态 |
| --- | --- | --- | --- |
| 个人作业1：Web 前端初探 | 对任意网页调研不同请求方式，至少 GET/POST；写出或截图请求及响应包；使用 JQuery 触发一个事件，写至少三句话并截图前后状态；完成一个浏览器插件并在文档中说明功能及代码。 | `docs/coursework/personal-1-web-request-jquery-plugin.md` | 待个人截图补齐 |
| 个人作业2：Web 前端设计 | 使用 Axure 等软件设计某个页面；为团队选取前台及后台模板；提交文档。 | `docs/coursework/personal-2-frontend-design.md` | 待个人原型截图补齐 |
| 个人作业3：开源建站工具初试 | 自行安装 WordPress 并做简单页面改造、设计布局；记录安装步骤和最终页面。 | `docs/coursework/personal-3-wordpress-trial.md` | 待个人实验截图补齐 |

## 团队作业

| 阶段 | 老师要求 | 项目对应 |
| --- | --- | --- |
| 团队作业1：需求文档 | 体育主题 2026 世界杯、队名、前后台功能、数据库 10+ 表、动态图形加分 | `docs/requirements.md`、`docs/v2-architecture.md`、`data/install.sql` |
| 团队作业2：设计文档 | 前后台页面、模块、数据库、页面流程 | `docs/design.md`、`docs/v2-architecture.md`、`docs/v2-database-dictionary.md` |
| 团队作业3：实现文档 | 记录主要代码实现和模块 | `docs/implementation.md`、原生 MVC 源码、真实 Git 记录和执行证据 |
| 团队作业4：用户手册 | 普通用户和管理员操作说明 | `docs/user-manual.md` |
| 团队作业5：部署文档 | 部署环境、部署流程、成功截图 | `docs/deployment.md` |
| 团队作业6：项目展示 | PPT：项目展示、分工、数据库设计、代码、文档分工、工作量分数 | `docs/presentation-outline.md` |

## 项目本体对应位置

- 队名：`worldcup-universe`。
- 技术栈：PHP + Yii2 + MySQL/MariaDB；不是旧版自定义 MVC 或 FastAPI/React 项目。
- 入口：`public/index.php` 启动原生 Yii2 Application；`controllers/`、`models/`、`views/` 分别承担 Controller、ActiveRecord 和 render，`services/` 承担业务事务与来源核对。
- 前台：`/`、`/worldcup`、`/worldcup/standings`、`/teams`、`/players`、`/news`、`/forum`、`/profile`、`/countries`、`/watch`、`/commentary`、`/vision`。`/fan-space` 保留为论坛旧链接；`/stats` 跳转至赛事中心的小组积分，不重复设数据栏目。
- 后台：`/admin`，可维护团队成员、球队、球员、赛程、新闻、发布内容、留言、数据来源和校验结果。
- 数据库：`data/install.sql` 是基础库入口，`migrations/` 记录增量修改；字段、外键与可空性见 `docs/v2-database-dictionary.md`。最终表数以实际迁移后的 schema 为准。
- 动态内容：附国旗的淘汰赛晋级图、按组展开的赛程/积分、3D 地球、图库及球员足球鼠标；新闻来自媒体 RSS/Atom 同步，不以生成的决赛简报冒充实时新闻。
- 页面组件：Arena（uiCookies）、Tabler、Multiverse（HTML5 UP）；署名与素材清单见 `/credits`、`docs/v2-media-manifest.json`。
- 课程材料：`/deliverables` 和后台 `coursework_files` 登记真实文件；路径限定在 `data/team/`、`data/personal/`，不能用不存在的文件生成下载成功状态。
- 版本位置：GitHub 仓库 `https://github.com/Aqs-1733/worldcup-universe.git`，工作分支 `codex/yii2-full-stack`。实际提交/远端状态另查 Git，不由本文推定。
- 2026 完整比赛：RTVE 官方决赛播放器是候选接入，元数据与播放状态分开；官方登录、DRM 和实际浏览器播放尚需另行验收，详见 `docs/v2-video-sources.md`。

## 最终提交建议

- 个人文档按老师格式单独打包：`作业1(学号_姓名).zip`、`作业2(学号_姓名).zip`、`作业3(学号_姓名).zip`。
- 团队文档建议统一用队名开头，例如：`worldcup-universe_需求文档(学号1_学号2_学号3).pdf`。
- 最终项目包建议包含：源码、数据库 SQL、部署文档、用户手册、项目展示 PPT、截图证明、GitHub 链接。
- 当前正式团队 PDF、项目展示 PPT、真人 5–15 分钟录屏及逐人确认的 M/V/C 与工作量仍需补交；四名成员资料可由已有个人作业核对，但原型角色标签不代表真实开发贡献已完成。
- `/data/team/`、`/data/personal/` 中现有 README 仅为收集说明，开发提纲须补真实内容并导出正式文件后再登记下载。
