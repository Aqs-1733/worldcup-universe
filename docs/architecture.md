# 技术架构

## 技术栈

- 前端：PHP 模板、CSS、少量原生 JavaScript。
- 后端：PHP 8.1+。
- 数据库：MySQL 8.0+，PDO 访问。
- 外部数据：FIFA、ESPN、Wikipedia、RSS 新闻源。
- AI：火山方舟 ARK OpenAI 兼容接口。

## 目录

- `public/index.php`：入口和路由。
- `app/Core`：环境变量、数据库、路由、认证、CSRF。
- `app/Controllers`：页面、登录注册、后台管理。
- `app/Repositories`：MySQL 读写。
- `app/Services`：HTTP、ARK、新闻同步、世界杯同步。
- `views`：页面模板。
- `database/schema.sql`：MySQL 表结构。
- `scripts`：安装和数据同步脚本。

## 数据库表

核心表包括：`users`、`roles`、`user_roles`、`team_members`、`data_sources`、`teams`、`players`、`coaches`、`venues`、`matches`、`match_stats`、`standings`、`news_sources`、`news_articles`、`tags`、`news_article_tags`、`comments`、`fan_preferences`、`user_favorite_teams`、`user_favorite_players`、`user_blocked_teams`、`admin_posts`、`ai_generations`、`audit_logs`。

## 课程交付留存

- `/coursework`：展示老师要求、个人作业模板和团队 1-6 作业状态。
- `coursework_artifacts`：MySQL 表，后台可维护每个阶段的证据路径、状态和备注。
- `docs/coursework-traceability.md`：把老师图片里的阶段要求映射到项目文档和页面。
