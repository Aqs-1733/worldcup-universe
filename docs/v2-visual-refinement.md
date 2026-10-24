# V2 前台视觉优化：实现与验收

日期：2026-10-22。基线：`origin/codex/yii2-full-stack`，`3230fc8`。
本地分支：`codex/v2-visual-refinement`。未提交、合并或推送。

工作区：`D:/internet/worldcup-teammate-preview`。
预览地址：http://127.0.0.1:8081/ 。预览库：`worldcup_teammate_preview_20261022`。
原 `D:/internet/worldcup-universe` 的 main 与原有未提交改动保留。

## 已实现

模块 | 实现 | 验证
---|---|---
首页与导航 | 明确首页入口与唯一当前页状态；全幅真实球场摄影、大标题、短序列入场；比分和真实数据概况移到 Hero 后 | 摄影加载成功，首页仅一个导航高亮，原真实比赛仍能访问
球员／球队 | 大型人物专栏、鼠标／方向键切换；保留官方照片与真实数据；目录采用两大幅／三小幅的编辑式节奏；首页球队主图不等宽 | 三个人物切换、单一可见面板、Messi 数据库搜索、移动端菜单
晋级路线 | 新横向单队路线；仅使用 verified MatchProgression 连接；被淘汰后的轮次留空；季军赛用已核验 loser 边、虚线区别；完整原始树仍可展开 | 遍历 32 支淘汰赛球队，每个显示节点与球队真实比赛集合完全一致，每条连接均存在于 verified edges
国家探索 | 原 Globe.gl 与 110m 地图保留；放大／缩小；稳定 NE_ID 显式映射；只读资料面板展示国家、球队、4 名球员、3 场最近比赛及详情链接 | 巴西、英格兰、佛得角、法国画布点击；中国无关联数据状态；拖动不误选；指针离开清 Hover
比赛影像 | 完整回放／集锦分栏；点击才加载官方 iframe；地区／登录说明、官方入口、加载超时、重试；集锦目录真实空状态 | 首屏不创建 iframe；非法类型 400；RTVE 封面加载，点击 Ver ahora 后实测地区限制，未宣称播放成功

## 修改入口

- `config/presentation.php`：Hero 摄影、替代文本、标题、描述、入口、主推球员 slug 顺序及转场时长。
- `public/assets/css/edition.css`：本轮独立样式和颜色 token；各模块都使用独立 class，原页面皮肤可继续保留。
- `views/site/index.php`、`views/layouts/main.php`：首页构图和导航。
- `views/catalog/_spotlight.php`、`players.php`、`teams.php`、`public/assets/js/edition.js`：人物专栏与编辑式目录。
- `views/catalog/_bracket-route.php`、`_route-match.php`、`matches.php`、`public/assets/js/bracket-route.js`：横向路线；原 `_bracket-tree.php` 没有删除。
- `config/globe-country-map.php`：NE_ID 到 country_code 的显式映射。英格兰／苏格兰保持独立。
- `services/GlobeCountryService.php`、`controllers/ApiController.php`、`config/web.php`：`GET /api/v1/globe/{geo_key}`。所有球队／球员／比赛查询限制到已核验记录。
- `views/countries.php`、`public/assets/js/globe-3d.js`、`country-panel.js`：相机、轮廓状态与国家资料面板；中断过期请求，避免快速选择造成面板错位。
- `controllers/VideoController.php`、`models/MatchVideo.php`、`services/VideoVerificationService.php`、`views/catalog/videos.php`、`public/assets/js/official-player.js`：视频分类、对应类型核验和播放降级。

## 运行检查

- 17 份主要 PHP 源文件 lint 通过；新增及修改 JS 语法检查通过。
- `php tests/backend.php`：42 项通过，测试写入回滚。独立预览库最初缺少管理员，补完安装所需角色及本地管理员后通过；未使用或修改原库账号。
- `php tests/official.php`：3,048 项官方来源检查通过。
- `php tests/visual-v2.php`：424 项只读映射、真实归属及类型验证通过。
- `node --test tests/globe-data.test.mjs`：4/4 通过。
- 浏览器：Chrome，1440×1000 / 390×844，六个模块共 12 个视图返回 200，无整页横向溢出、无应用脚本异常；另测鼠标、键盘与 reduced-motion。
- Playwright 交互结果：`D:/internet/worldcup-v2-evidence/interaction-results.json`。
- 运行前后截图：`D:/internet/worldcup-v2-evidence/`，详见同目录 `index.md`。
- `git diff --check` 通过。

## 真实边界与剩余事项

1. RTVE 官方播放器实际显示：`Este contenido tiene restringidos los derechos de emisión en su ubicación`。此环境没有成功播放，视频元素未进入播放状态。证据：`video-result.json`、`video-playback-result.png`。没有登录他人账号、提取流或绕过 DRM。
2. 数据库只有一条完整回放，没有已核验集锦；没有补写猜测的 YouTube ID。模型／核验服务已支持单独的 highlights 类型，后续正式录入仍必须通过官方核验。
3. 110m 地图不包含佛得角、库拉索的可辨认 Polygon；通过数据库选择器可聚焦其经纬度并查看资料，不伪造岛屿几何。
4. 本轮以布局与手动切换为主；未加入滚动劫持、自动轮播、复杂后处理。手机是真实 Chrome 的移动视口模拟，尚未完成实体 iOS／Android 设备验收。
5. 上一轮缺失 vendor 样式的恢复仍包含在工作区；来源与恢复边界见 `public/assets/vendor/RECOVERY.md`。原清单中四份自定义适配 CSS 的 hash 不适用于恢复版本。
6. 球场背景是已署名的历史摄影，不将它标记为 2026 比赛现场照片。运动数据和原始来源文件没有被视觉实现改写。
