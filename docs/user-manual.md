# 团队作业4：用户手册

## 普通用户

1. 打开 `http://127.0.0.1:8080`。
2. 点击“注册”，填写用户名、邮箱和密码。
3. 初次登录可填写世界杯偏好问卷，也可以跳过。
4. 在首页查看赛程、球队、球员、新闻和团队信息。
5. 在“球迷空间”修改支持球队、支持球员、屏蔽球队和推送偏好，并发布留言。
6. 在“智能解说”输入主题、语气和球队；只有勾选图片生成时才会调用图片模型。

## 管理员

1. 使用 `.env` 中的管理员账号登录。
2. 进入 `/admin` 后可维护团队成员、球队、球员、赛程、新闻、发布内容、留言、国家资料、来源文件和数据校验记录。
3. 在新闻页点击“联网刷新”可同步最新 RSS 新闻；失败时系统记录错误，不填假数据。
4. 修改 `.env` 后可重新运行 `php scripts/install.php` 更新基础配置。

## 常用脚本

```powershell
php scripts/install.php
php scripts/import_collected_data.php
php scripts/fill_player_name_transliterations.php
php scripts/sync_news.php
php -S 127.0.0.1:8080 -t public public/index.php
```
