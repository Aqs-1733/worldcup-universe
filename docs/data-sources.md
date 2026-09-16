# 数据来源

本项目要求真实数据优先，来源字段会保存在 MySQL 中。

## 赛事

- FIFA Teams：`FIFA_TEAMS_URL`
- FIFA Scores & Fixtures：`FIFA_FIXTURES_URL`
- FIFA Standings：`FIFA_STANDINGS_URL`
- ESPN Scoreboard：`ESPN_SCOREBOARD_URL`
- ESPN Standings：`ESPN_STANDINGS_URL`

## 球员

- Wikipedia 2026 FIFA World Cup squads：`WIKIPEDIA_SQUADS_URL`

球员同步会保存原名，配置 ARK 后再写入中文译名。找不到的字段不补假值。

## 新闻

新闻源在 `news_sources` 表中维护，默认包含 BBC Sport、ESPN、The Guardian、CBS Sports、The New York Times 和 Sky Sports RSS。新闻刷新时会同步翻译标题和摘要，支持多标签分类；默认先用 ARK 轻量批量翻译，失败时再尝试 MyMemory 在线翻译，AI 创作和生图仍使用 ARK。
