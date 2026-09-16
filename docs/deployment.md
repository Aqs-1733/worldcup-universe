# 团队作业5：部署文档

## 1. 环境

- PHP 8.1+，启用 `pdo_mysql`、`curl`、`simplexml`、`dom`、`mbstring`。
- MySQL 8.0+ 或 XAMPP MariaDB/MySQL。
- 推荐本机路径：`D:\worldcup-ai-universe`。

## 2. 配置

复制 `.env.example` 为 `.env`，设置数据库连接、管理员账号和可选 ARK 配置。真实密钥只放 `.env`，不要提交到 GitHub。

## 3. 初始化数据库

```powershell
cd D:\worldcup-ai-universe
D:\XAMPP\php\php.exe scripts\install.php
D:\XAMPP\php\php.exe scripts\import_collected_data.php
D:\XAMPP\php\php.exe scripts\fill_player_name_transliterations.php
D:\XAMPP\php\php.exe scripts\sync_news.php
```

## 4. 启动项目

```powershell
D:\XAMPP\php\php.exe -S 127.0.0.1:8080 -t public public/index.php
```

访问：`http://127.0.0.1:8080`。

## 5. 验证

- 首页、赛程、球队、球员、新闻、AI、课程交付页面返回 200。
- `/admin` 登录后可进入后台。
- MySQL 中球队、球员、比赛、新闻、课程交付清单均有数据。
