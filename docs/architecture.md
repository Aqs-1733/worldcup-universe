# 技术架构

## 技术栈

- 前端：PHP 模板、CSS、少量原生 JavaScript。
- 后端：PHP 8.1+。
- 数据库：MySQL 8.0+，PDO 访问。
- 外部数据：FIFA、ESPN、Wikipedia、RSS 新闻源。
- 智能服务：火山方舟 ARK OpenAI 兼容接口，用于客服问答、解说和搜索总结。

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

核心表包括：`users`、`roles`、`user_roles`、`team_members`、`data_sources`、`teams`、`players`、`coaches`、`venues`、`matches`、`match_stats`、`standings`、`news_sources`、`news_articles`、`tags`、`news_article_tags`、`comments`、`fan_preferences`、`user_favorite_teams`、`user_favorite_players`、`user_blocked_teams`、`admin_posts`、`ai_generations`（智能生成记录）、`audit_logs`、`country_profiles`。

## 国家探索模块

- `/countries`：世界杯参赛国家、主办城市、场馆、地图和旅行文化摘要。
- `country_profiles`：MySQL 表，保存 mledoze/countries 同步的国家基础信息。
- `scripts/sync_country_profiles.php`：根据球队 `country_code` 联网抓取国家信息。
