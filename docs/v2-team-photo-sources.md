# 2026 世界杯球队合照

采集与复开日期：2026-10-22（北京时间）。本轮取得 **5 队**官方来源合照：德国、挪威、哥伦比亚、巴西、英格兰。其余 **43 队**在清单中保留 `path: null`，不使用个人球员、旧年球队、预选赛图片或生成图片冒充当届球队合照。

## 官方来源与年份证据

| FIFA 代码 | 官方来源 | 证据与素材 |
|---|---|---|
| GER | [德国足协：2026世界杯官方球队照片](https://www.dfb.de/news/das-ist-das-offizielle-mannschaftsfoto-fuer-die-wm-2026) | 2026-06-10 发布，标题明确指向 WM 2026 的官方全队合照；图注包含球员与工作人员。保留官方原图 2720×1530 JPEG。 |
| NOR | [挪威足协：世界杯专题页说明](https://www.fotball.no/landslag/norge-a-herrer/2026/her-er-var-mesterskapsside/) | 2026-06-15 发布，文章明确为 VM 2026，配图 alt 标识正式世界杯阵容合照；1280×853 WebP，官方发布版本原样缓存。 |
| COL | [哥伦比亚足协世界杯图库](https://fcf.com.co/category/galeria-camino-al-mundial/) | 2026-07-03 的图库条目，配图图注明确写哥伦比亚球员在该年世界杯对加纳赛前合影；1052×701 JPEG，非其他年份或友谊赛照片。 |
| BRA | [巴西足协媒体库：2026世界杯官方照片](https://cbf.media/en/p/foto-oficial-copa-2026-masculino-01062026/a/34f9dc8a-2049-46fc-b036-771df00820fe) | 2026-06-01 的官方世界杯出征合照；1619×1080 JPEG，球员与随队人员在飞机前合影。**官方公开预览含大幅水印，已原样保留**。 |
| ENG | [英格兰足协：官方全队照](https://www.englandfootball.com/articles/2026/Jun/12/england-mens-senior-team-marks-and-spencers-squad-photo-20261206) | 2026-06-12 发布，标题为官方全队照，图像 alt 明确球队为世界杯拍照；1440×810 PNG，着正式出征服装的全队合影，不称为比赛现场照。 |

年份和球队语义由官方页面标题、图注/alt、条目日期共同支持，不以图片 URL 的“2026”字样单独判定。现有 48 队 FIFA team JSON 的 `PictureUrl` 是国旗，`Content` 为空且 `ThumbnailUrl` 为 null，未找到统一的球队合照字段；没有把它们当作已采集到 48 张合照。

## 文件与接口

- `public/assets/media/team-photos/manifest.json`：顶层 `teams` 按 FIFA 三字母代码索引。每队包含 `team_code`、`year`、`path`、`source_url`（官方页面）、`image_url`、`caption`、`caption_evidence`、`year_evidence`、`date`、`sha256`、`bytes`。成功项补充尺寸、格式、署名、水印与原始来源证据；缺失项图像字段为 null。
- `public/assets/media/team-photos/visual-review.json`：五张本地原文件的人工视觉复核及 SHA-256。确认均为多人球队/代表团合影，不是单人肖像；该复核不尝试识别所有人物。
- `public/assets/media/team-photos/evidence/*.source.txt`：官方来源页面的原始字节文本及清单中的哈希，使用 `.txt` 而非可执行 HTML。
- `scripts/fetch_team_photos.py`：有界官方来源选择器，可复用缓存来源文本；`--refresh` 重新请求官方页面。按实际图像格式保留后缀，不解码重存、不改像素。以后下载文件哈希变化，状态退回等待视觉复核，而非沿用旧复核结论。

当前可展示项状态为 `verified_2026_team_photo`；5 张图像共 **8,253,459 字节**。巴西 `watermarked=true`，如不适合作封面可以继续显示国旗，不去除水印。来源署名不等于新增开放转载许可；清单保留官方署名与来源，不伪称 CC 授权。

本轮只新增素材、采集脚本与本文，不修改数据库、模型、视图或 CSS，不表示网站已完成这批合照的接入或发布。

主分支接入状态：球队列表、首页球队卡片及球队详情封面已使用 TeamPhoto 校验器读取上述清单；43 队缺失合照时明确回退为国旗设计，个人照片仅用于球员模块和阵容卡片。
