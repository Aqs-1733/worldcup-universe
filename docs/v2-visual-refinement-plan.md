# V2 前台二次视觉优化 · 2026-10-22

基线：`origin/codex/yii2-full-stack` / `3230fc8`。
独立工作区：`D:/internet/worldcup-teammate-preview`，本地分支 `codex/v2-visual-refinement`。
保留上轮尚未提交的静态资源恢复；不自动提交、合并或推送。

## 设计与范围

沿用本地真实摄影、官方球员照片、红色队徽和原生 Yii2 页面。
首页采用全幅摄影、巨大但克制的赛事标题和底部章节导航；比分保留在首屏下方。
球员页使用手动切换、键盘可操作的大型 Spotlight，目录改为不等比例编辑式布局。
色彩：球场黑 `#0b1219`、暖白 `#f5f4f0`、赛事红 `#d62630`、奖杯金 `#d8bb7a`、文字灰 `#8c959e`。
字体复用本地 Anton、WCU Sans 与 Mulish。转场仅用短时透明度／位移，遵循 reduced-motion。

## 顺序及验收

1. 首页、导航与人物专栏：修改 `views/site/index.php`、`views/layouts/main.php`、`views/catalog/_spotlight.php`、`views/catalog/players.php`；文案和摄影集中在 `config/presentation.php`，CSS/JS 独立。验证首页入口、主推切换、搜索筛选、手机布局。
2. 晋级路线：使用现有 verified `MatchProgression`，由真实边关系生成横向轮次及连接线；季军赛单独显示。验证每个节点唯一、每条线对应数据库、按队高亮和比赛详情链接。
3. 国家探索：复用 Globe.gl、110m 地图、稳定 NE_ID，显式地图键映射到数据库 country_code；增加只读资料 API、球队／球员／比赛面板及缩放按钮。英国地图单元不足时显示关联协会列表，不猜测球队。验证参赛与无关联地区、加载失败、键盘入口、缩放和移动端。
4. 视频：检查 `MatchVideo` 和 `VideoVerificationService`；完整回放／集锦使用明确类型，空目录如实展示。延迟挂载官方 iframe，始终提供来源与地区／登录说明及失败恢复。实际检查官方播放器结果，不提取流、不修改体育快照。
5. 桌面 1440px、移动 390px 截图及交互验证；PHP lint、现有适用测试和真实关系校验。截图保存在 `D:/internet/worldcup-v2-evidence/`，记录已验证项与外部播放限制。

## 数据约束

不更改球队、球员、赛程、比分或晋级业务数据。所有查询只读取已核验记录。
不存在的集锦不补假链接；浏览器无法跨域确认播放时不声称已成功播放。
不承诺自动动画或滚动转场作为本轮的主要交付，先完成可用的布局与交互。
