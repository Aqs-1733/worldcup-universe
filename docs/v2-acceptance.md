# 验收记录

验收对象：codex/yii2-full-stack；本机 Yii2 / PHP + MariaDB 3307；2026-10-21～22。所有断言只表示已执行的检查，不代替人工课程交付。

检查 | 观察结果 | 证据
---|---|---
后端数据库／服务 | 42 项，测试写入回滚 | artifacts/backend-tests.txt
官方来源 | 3,048 项；48 队／1,248 球员／104 场／5,323 阵容／32 晋级边 | artifacts/official-tests.txt
HTTP 流程 | 111 项，登录、CSRF、权限、主队、论坛、管理、下载、视频与导航 | artifacts/http-tests.txt
安装与迁移回环 | 全新库 48 表；down 后原 34 表和原密码保留；up 与官方重放成功 | artifacts/schema-roundtrip.txt
地球数据与交互约束 | 4 项通过 | artifacts/globe-tests.txt
中文 UI | 14 个公开路由，功能性中文标题、剔除旧口号 | tests/chinese_ui.py
国旗覆盖 | 280 个团队显示容器，图标与中文替代文本 | tests/flag_ui.py
导航去重 | 6 个主导航、唯一积分入口、旧 /stats 为301 | tests/navigation_ui.py
新闻 | 7 个真实来源、短摘要和来源图片、6 类标签；生成简报不公开 | artifacts/news-tests.txt
素材 | 56 张官方肖像、10 张署名原图、6 张原站预览、真实足球贴图 | docs/v2-media-manifest.json

新闻数量会随 worker 更新；文章原文、官方图片和数据的时效以记录的采集时间为准。FIFA 数据是核验快照，不宣称比分直播。

视觉验收包括桌面／窄屏的论坛分栏与按钮边界、各模块窄横幅、国旗、球员肖像、新闻来源和折叠组交互。截图及 DOM 几何记录见 artifacts/screenshots。

## 尚需真人或第三方状态

- RTVE 官方账号登录后的视频流播放未验证；保留官方观看入口。
- 成员实际贡献签认、最终课程 PDF/PPT、本人讲解录屏与个人交付由真实成员完成。见 docs/v2-coursework.md 和 data/team、data/personal 清单。
- 付费／凭据依赖的 Ark AI 服务需部署者配置；未将缺失配置包装成已完成模型调用。

测试脚本仅向本机专用测试环境创建带精确 qa_ 前缀的临时账号，结束按本轮前缀清理，不能在真实用户库中批量匹配删除。

2026-10-22 浏览器实测：官方 iframe 标题和封面已加载；点击播放后，RTVE 返回所在地播放权限制（“Este contenido tiene restringidos los derechos de emisión en su ubicación”）。本站显示官方登录／地区限制提示，未播放成功，不绕过地区或 DRM 校验。

2026-10-22 截图修订另见 artifacts/redesign-20261022/VERIFICATION.txt。旧 artifacts/screenshots 和 navigation 截图作为前一版历史记录保留，当前新版截图位于 artifacts/redesign-20261022/screenshots。

2026-10-22 后续修订：48/48 队真实 2026 合照已接入（缺失 0），逐队来源、图注、日期、图片／来源证据哈希及视觉复核均保留；48 个列表封面、48 个原生详情封面、48 张 HTTP 图像哈希通过。另修正 `/forum/35` 帖子详情曾遗漏的回复区，零回复不再显示旧灰色横幅，会员引用／取消引用及锁定态实测通过。28 项详情流程、111 项 HTTP、42 项后端及 3,048 项官方来源检查通过；临时测试账号与内容已精确清理。最新证据及桌面／390px 截图见 `artifacts/topic-20261022/VERIFICATION.txt` 和其 `screenshots/` 目录。
