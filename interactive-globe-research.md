# Interactive Globe 升级为世界杯 3D 交互地球：技术调研

> 调研日期：2026-09-21  
> 范围：只读检查项目代码、目录、数据库结构与本地运行数据；本文是方案调研，**未修改现有实现代码、数据库或接口**。  
> 结论中的“建议新增/修改”均为后续实施建议，不代表已经执行。

## 1. 结论摘要

当前首页（实际是“国家探索”页）的 `Interactive Globe` 不是 3D 地球：它是 `views/countries.php` 中的内联 SVG 绿色色块，加上 `public/assets/css/app.css` 的渐变、网格和动画，再由 `public/assets/js/app.js` 根据国家卡片经纬度移动一个标记点。它没有真实国家边界、Canvas/WebGL、鼠标/触摸拖拽或地图点击。

项目适合做真实 3D 地球，但推荐保持现有 Yii2 + 服务端渲染 PHP 架构，在 `countries` 页面局部引入 **globe.gl（底层为 Three.js / three-globe）+ 本地预处理的 Natural Earth 地理数据**。这避免为了一个组件把项目迁移到 React；同时可获得真实球面国家 Polygon、拖动惯性、光照、自动旋转、射线点击与相机聚焦。若后续需要自定义着色器、复杂多层灯光或极致渲染控制，再下沉为 `Three.js + three-globe + OrbitControls`，不需要迁移整个站点。

当前代码中的球队、球员、比赛、赛况、事件、首发、技术统计和积分榜表/仓储方法已经具备较好的“参赛队详情”数据模型；但本地正在运行的数据库中这些表均为 **0 行**。README 所说的 48 队、1,248 名球员、104 场比赛等是完整导入包成功后的目标结果，不能视为当前运行数据。完整导入包目录在当前工作区也未找到，故其真实内容与导入结果为“待确认”。

“所有国家可点”不能只依赖当前 `country_profiles`：它目前由参赛队驱动同步，且同步脚本会删除没有球队的国家资料。因此需要将“全世界地理国家实体”和“FIFA/世界杯球队实体”分开，并建立显式映射。

## 2. 当前实现分析

### 2.1 页面与交互流程

`/countries` 路由由 Yii2 的 `app\controllers\SiteController::actionCountries()` 转发给旧页面控制器 `App\Controllers\PageController::countries()`。后者通过 `App\Repositories\AppRepository::countryProfiles()`、`venues()`、`teams(null, 120)` 取数，并渲染国家探索页。

页面中的地球结构位于 `views/countries.php`：

- `div.globe-card[data-country-globe]`：模块容器和“国家数/球队数”计数。
- `div.globe-sphere[data-globe-sphere]`：视觉球体。
- `div.globe-map[data-globe-map][aria-hidden="true"]`：内联 `<svg viewBox="0 0 360 180">`，由 6 个手工写死、经过 `translate(...)` 重复的 `<path>` 组成；它们只是大洲轮廓印象，不是 GeoJSON/真实海岸线或国家边界。
- `div.globe-active-marker`：唯一会随选择变化的国家定位点。
- `article.country-card[data-country-card]`：下方国家卡片；卡片点击才会触发地球目标位置更新。

卡片向前端脚本提供 `data-code`、`data-name`、`data-capital`、`data-region`、`data-flag`、`data-lat`、`data-lng`。卡片目前不是到国家详情页的链接；球队详情已有 `/teams/<id>` 路由，但没有 `/countries/<id>` 详情路由。

`public/assets/js/app.js` 的地球脚本读取这些 data attribute，维护 `centerLat`、`centerLng`、`targetLat`、`targetLng` 与 `focusedUntil`：

1. 点击国家卡片时，`selectCountry(point, scroll)` 更新目标经纬度、激活卡片并更新文字。
2. `requestAnimationFrame` 的 `tick()` 在空闲时缓慢漂移，在聚焦期插值转向目标。
3. `project()` 用正射投影近似计算单个 marker 的屏幕位置和是否在可见半球。
4. 脚本写入 `--spin-lng`、`--globe-tilt` CSS 变量，令 SVG 图层发生平移/倾斜的视觉变化。

因此当前“自动旋转到对应位置”是可用的视觉模拟，但国家形状没有参与投影和命中测试；SVG 还标注了 `aria-hidden="true"`，不能被点击。

### 2.2 样式实现

`public/assets/css/app.css` 中 `.globe-card`、`.globe-sphere`、`.globe-sphere-clean`、`.globe-map`、`.globe-active-marker` 等规则通过径向渐变、重复线性渐变、伪元素、阴影和 keyframes 制造海洋、高光、经纬网与转动效果。没有 `<canvas>`、`WebGLRenderingContext`、GPU 网格、真实光源、相机或 3D 控制器。

这很好地解释了截图中的蓝色球体与右侧绿色陆地区块：它们来自 CSS/SVG，而不是地理数据。

## 3. 当前相关代码与数据文件

| 文件 | 已有职责 | 与 3D 地球的关系 |
|---|---|---|
| `D:/internet/worldcup-universe/views/countries.php` | 国家探索页、现有 SVG 地球、国家卡片和 data attribute | 现有地球的唯一页面结构；后续替换/保留降级容器的位置 |
| `D:/internet/worldcup-universe/public/assets/js/app.js` | 手写页面交互，含国家卡片→地球 marker 动画 | 现有伪地球逻辑；无模块化/无 WebGL |
| `D:/internet/worldcup-universe/public/assets/css/app.css` | 页面样式、球体渐变、SVG 位移与 marker | 现有视觉主题可复用；球体规则需逐步替换或作为降级样式 |
| `D:/internet/worldcup-universe/controllers/SiteController.php` | Yii2 路由 action 到页面控制器的桥接 | 后续若增加 JSON 端点/国家详情，需按此模式扩展 |
| `D:/internet/worldcup-universe/app/Controllers/PageController.php` | 各页面取仓储数据后渲染 | `countries()` 是当前入口；可作为详情聚合的服务端入口 |
| `D:/internet/worldcup-universe/app/Repositories/AppRepository.php` | 国家、球队、球员、比赛、积分榜、比赛统计等查询与 upsert | 已有球队/比赛详情数据入口；尚无地球专用聚合/JSON 查询 |
| `D:/internet/worldcup-universe/config/web.php` | URL 规则 | 当前无 `/api/globe` 或国家详情路由 |
| `D:/internet/worldcup-universe/database/schema.sql` | MySQL 表结构、索引与约束 | 现有世界杯实体较全；全世界地理实体/映射尚缺 |
| `D:/internet/worldcup-universe/scripts/import_collected_data.php` | 完整采集包导入和 FIFA 队码→国家码转换 | 已含英格兰/苏格兰特例；是 ID 一致性风险点 |
| `D:/internet/worldcup-universe/scripts/sync_country_profiles.php` | 从 `mledoze/countries` 同步球队所属国家资料 | 当前只同步有球队的国家，并删除无球队资料，不支持全世界点击 |
| `D:/internet/worldcup-universe/scripts/fill_derived_data.php` | 补齐派生数据、检查国家资料 | 明确把 `GB-ENG`、`GB-SCT` 视作独立球队国家 |
| `D:/internet/worldcup-universe/app/Services/WorldCupSyncService.php` | 在线世界杯同步及队伍国家码推导 | 对英国四协会的映射与完整导入器不一致，需统一 |
| `D:/internet/worldcup-universe/views/team.php` | 球队详情与球员显示 | 地球点击参赛国后可链接/复用 |
| `D:/internet/worldcup-universe/views/match.php` | 单场比赛、统计、事件、首发 | 地球国家详情内的比赛入口可复用 |
| `D:/internet/worldcup-universe/views/worldcup.php` | 小组赛、淘汰赛、积分榜展示 | 国家详情可复用其赛事数据 |
| `D:/internet/worldcup-universe/composer.json` | PHP Composer 依赖 | 仅 Yii2/PHP 依赖；没有 Node、React、Three.js 或前端打包链 |

项目中未发现 `package.json`、TypeScript 源文件、React 组件、Three.js/globe.gl 依赖、Canvas/WebGL 代码或已下载的 GeoJSON/TopoJSON 资源。

## 4. 当前前端技术栈判断

- 后端/模板：PHP 8.1+、Yii2、服务端渲染 PHP view。
- 浏览器端：单一手写 `public/assets/js/app.js` 与 `public/assets/css/app.css`，以 DOM 和 CSS 动画为主。
- 数据库：MySQL 语法的 DDL/查询（例如 `UNSIGNED`、`ON DUPLICATE KEY UPDATE`）。
- 未使用：React、TypeScript、Canvas、WebGL、Three.js、React Three Fiber、globe.gl、TopoJSON/GeoJSON 前端库。

这意味着 React Three Fiber 不应作为首次升级的默认选项：它需要引入 React 运行时、组件构建和 bundler，改动边界远超当前单一页面功能。相反，标准 ES module 或本地打包的普通 JavaScript 可与现有 PHP 页面并存。

## 5. 当前数据库与接口能力

### 5.1 已有表与可支撑内容

| 领域 | 表/关键字段 | 仓储能力 | 可支持的地球详情 |
|---|---|---|---|
| 球队 | `teams.id`、`fifa_id`、`slug`、`code`、`country_code`、`name_cn`、`group_name`、`world_ranking` | `teams()`、`team()` | 参赛队标识、球队页、分组/排名 |
| 球员 | `players.team_id`、`fifa_id`、`slug`、位置、俱乐部、出场/进球 | `players()`、`player()` | 球队球员列表和个人详情入口 |
| 场馆 | `venues.id`、`city`、`country_code`、容量 | `venues()` | 比赛所在场地/城市 |
| 赛程/比分 | `matches.external_id`、`stage`、`group_name`、`home_team_id`、`away_team_id`、比分、状态、开球时间、`venue_id` | `matches()`、`match()`、`groupMatches()` | 国家关联赛程、已赛比分、比赛详情入口 |
| 比赛统计 | `match_stats.match_id`、`team_id`、控球、射门、xG 等 | `matchStats()` | 对应比赛统计 |
| 事件/阵容 | `match_events`、`match_lineups`，均关联 match/team/player | `matchEvents()`、`matchLineups()` | 进球事件、首发、替补 |
| 球员统计 | `player_statistics.player_id`、比赛/赛季统计字段 | 查询/写入模型存在 | 球员/球队统计汇总的基础 |
| 小组积分 | `standings.team_id`、`group_name`、胜平负、得失球、积分 | `standings()` | 参赛队小组表现 |
| 国家资料 | `country_profiles.country_code`、名称、首都、区域、经纬度、人口、面积、地图链接、`fifa_team_count` | `countryProfiles()` | 参赛队所属国家卡片及聚焦坐标 |

`AppRepository` 还已有 `upsertTeam()`、`upsertPlayer()`、`upsertMatch()`、`upsertMatchStats()` 等同步/导入接口。因此，**在完整数据成功导入后**，点击参赛国后展示球队、球员、比赛、比分、赛程、统计的底层表关系基本齐备。

### 5.2 当前路由和接口

已存在页面路由：`/countries`、`/teams`、`/teams/<id>`、`/players`、`/players/<id>`、`/worldcup`、`/matches/<id>`、`/search`。`config/web.php` 中唯一的 API 风格路由是 `api/news/refresh`；没有国家详情 JSON、地球聚合 JSON 或地理数据服务接口。

因此 v1 可以将“点击国家”跳转到后续新增的服务端国家详情页，也可以为不刷新体验新增只读 JSON 端点。后二者目前均未实现。

### 5.3 本地实际数据状态与导入风险

对当前本地数据库的只读计数检查显示：`teams`、`players`、`matches`、`standings`、`match_stats`、`match_events`、`match_lineups`、`country_profiles`、`venues`、`player_statistics` 均为 **0 行**。

README 叙述完整采集包导入后应有 48 支球队、1,248 名注册球员、104 场比赛及大量原始记录；这是设计目标/导入成功后的预期，不是当前库中已验证的内容。当前工作区未找到 README 指向的 `storage/imports/worldcup2026_complete_collection_kit`，所以完整导入包的存在、字段完整性、2026 实际赛程与统计覆盖率均为 **待确认**。

## 6. 已有数据、缺失数据与 ID 对齐

### 6.1 当前 ID 的实际含义

| 位置 | 当前 ID/字段 | 观察 | 风险 |
|---|---|---|---|
| `teams` | `id` | 本地数据库主键 | 适合内部关联，不可作为地图跨版本 ID |
| `teams` | `fifa_id` | 唯一字段，来源型 FIFA/外部 ID | 实际来源格式与稳定性待确认 |
| `teams` | `code` | 导入器按 `ENG`、`SCO`、`USA` 等足球三字码使用 | 应视作 FIFA/数据源球队码，不应猜作 ISO3 |
| `teams` | `country_code` | `VARCHAR(8)`，供国家资料、场馆等关联 | 混用 ISO alpha-2 和英国扩展码，非单一国际标准 |
| `country_profiles` | `country_code` | 唯一键，当前与 `teams.country_code` 关联 | 不包含独立 ISO3/numeric/map feature ID |
| 地图数据 | 当前不存在 | 无 GeoJSON/TopoJSON feature ID | 不能按名称可靠连接 |

`scripts/import_collected_data.php::countryAlpha2()` 已把常见球队代码转为 ISO alpha-2，例如 `USA → US`、`MEX → MX`；同时明确把 `ENG → GB-ENG`、`SCO → GB-SCT`。`scripts/fill_derived_data.php` 也把这两个代码作为独立国家资料处理。

但 `app/Services/WorldCupSyncService.php` 中 `countryCodeFromTeam()`/`countryCodeFromName()` 又将 `ENG`、`SCO`、`WAL` 或相应名称归为 `GB`。这是同一业务概念在两条导入路径上的不一致，必须先收敛，不能在前端通过名称猜测修补。

### 6.2 特殊地理国家与 FIFA 协会

英格兰、苏格兰、威尔士、北爱尔兰构成最明确的案例：普通 ISO 国家层级地图通常只给出 `GBR`（United Kingdom），但 FIFA/足球协会与球队是可分开的。Natural Earth 的 **Admin 0 Map Units** 数据说明包含 England、Scotland、Wales、Northern Ireland 等构成单元，因此比只含 Admin 0 Countries 的标准世界文件更适合作为本项目主地图来源。

建议建立显式映射，而非按中文/英文显示名匹配：

| 业务对象 | 推荐规范键示例 | 地图几何 | FIFA/球队关系 |
|---|---|---|---|
| 联合王国整体地理国家 | `GBR` | 全英国 Polygon 或其组成单元集合 | 不作为英格兰/苏格兰队的唯一归属 |
| 英格兰 | `GB-ENG` | England map unit | 映射 `teams.code = ENG` |
| 苏格兰 | `GB-SCT` | Scotland map unit | 映射 `teams.code = SCO` |
| 威尔士 | `GB-WLS`（建议键） | Wales map unit | 映射 `WAL`；当前完整导入器是否会出现/采用该键为待确认 |
| 北爱尔兰 | `GB-NIR`（建议键） | Northern Ireland map unit | 映射对应 FIFA 队码；本届是否参赛取决于导入数据 |

其他类似场景（Kosovo、Chinese Taipei、Hong Kong、Puerto Rico、Curaçao、海外属地、争议地区及法国海外领地）也不应由 UI 名称推导。应在预处理映射文件/表中逐条审核；本项目采用的主地图数据版本和产品对于争议边界的展示政策为 **待确认**。

### 6.3 已有数据可覆盖与不能覆盖的范围

| 目标 | 当前可覆盖程度 | 缺口 |
|---|---|---|
| 参赛国家高亮 | 数据模型可覆盖；当前本地无数据 | 完整导入、球队→地图映射、地理文件 |
| 点击参赛国看球队/球员/赛程/比分/比赛统计 | 表关系与现有详情页可覆盖 | 国家聚合查询/路由或 JSON，实际导入数据 |
| 点击所有国家 | 不可覆盖 | 全世界国家目录、全部 geometry、非参赛国家资料保留策略 |
| 显示“未参加/未晋级本届世界杯” | 只能粗略从“没有参赛球队”推断 | “未参赛”与“未晋级”没有资格赛状态字段，不能准确区分 |
| 大洲点击和聚焦 | 不可覆盖 | continent→国家 feature 映射、地图命中层、相机控制 |
| 真实国家边界 | 不可覆盖 | 版本化、预处理的 GeoJSON/TopoJSON |

## 7. 3D 地球方案对比

| 方案 | 优点 | 对当前项目的代价/限制 | 结论 |
|---|---|---|---|
| 原生 Three.js + 手工国家网格 | 渲染、材质、光照、相机完全可控 | 需要自行完成 GeoJSON 三角化、球面投影、边界、射线命中和性能治理；实施风险最高 | 不建议 v1 从零开始 |
| Three.js + three-globe | 基于 Three.js 的球体/Polygon 层，可自行接入 `OrbitControls`、灯光和主题；控制力强 | 仍需自己管理 scene、renderer、camera、循环、响应式和销毁 | 适合后续需要深度定制时 |
| globe.gl（Three.js/three-globe 的高层封装） | 可直接提供球体、GeoJSON Polygon、hover/click、标签/点/弧线、相机视点与控件；普通 JavaScript 可嵌入现有 PHP 页 | 封装会限制少量极深层 render pipeline 调整；仍应锁定本地版本并做设备测试 | **推荐 v1** |
| React Three Fiber + Drei | React 组件化，React 项目内开发体验好，支持 declarative scene | 项目没有 React、JS 构建链或组件架构；仅为一个模块引入会扩大改造范围 | 当前不推荐 |
| CSS/SVG/D3 伪球/2D 地图 | 体积较小，兼容性高 | 难实现真实立体、自然拖拽和球背面裁剪；不能满足明确的真实 3D 目标 | 仅作 WebGL 降级方案 |

Three.js 官方 `WebGLRenderer` 以 WebGL2 为基础；`OrbitControls` 原生支持 damping 和 auto rotate，且需在动画循环中调用 `update()`，符合用户提出的惯性与自动旋转需求。React Three Fiber 官方文档说明它是 React renderer，安装前提是 React，因此与当前架构不匹配。[Three.js WebGLRenderer 文档](https://threejs.org/docs/pages/WebGLRenderer.html) [Three.js OrbitControls 文档](https://threejs.org/docs/pages/OrbitControls.html) [React Three Fiber 安装文档](https://r3f.docs.pmnd.rs/getting-started/installation)

globe.gl 的官方示例直接将 GeoJSON Feature 传给 `polygonsData(...)`，再以 polygon cap/side/stroke 颜色呈现国家，因此不必在本项目中手写国家多边形的球面投影和三角化。其底层 three-globe 也有国家 Polygon 示例。[globe.gl](https://globe.gl/) [three-globe 国家 Polygon 示例](https://app.unpkg.com/three-globe%402.37.4/files/example/country-polygons/index.html)

## 8. 推荐技术方案

### 8.1 推荐组合

**推荐：在 `/countries` 的局部容器中使用锁定版本、随项目发布的 globe.gl + Three.js，加载本地预处理的 Natural Earth Admin 0 Map Units 数据。**

理由：

1. 保持 Yii/PHP 和当前普通 JavaScript 页面，不引入 React 全站改造。
2. `polygonsData` 直接适配真实国家/地图单元的 GeoJSON `Polygon` / `MultiPolygon`，可支持国界、hover、点击、高亮和背面剔除。
3. 内建球面、交互控制和视点控制可减少手写 3D 数学，重点可放在世界杯数据、映射和视觉主题。
4. Three.js 底层仍可提供深蓝海洋、紫色大气、金色参赛国、边界描边、环境/方向光和粒子等世界杯科技风格。
5. 若产品后续需要更定制的 shader、地图层或镜头语言，可从 globe.gl 迁至 `three-globe + Three.js`；地理数据、映射表和后端接口均可复用。

实现时应使用本地静态资源/构建产物，不依赖生产环境 CDN。当前项目没有 Node 构建链；后续可选择最小化前端打包步骤，或在明确版本后将 vendor bundle 受控地放入 `public/assets/`。具体 bundle 方式及最终库版本为 **待确认**，需要结合上线部署方式决定。

### 8.2 建议的视觉分层

1. 深蓝球体底色、低分辨率海洋/夜光纹理或纯材质（避免过大纹理）。
2. 真实 Map Unit Polygon：未参赛国低饱和蓝紫/半透明；参赛队国家金黄；hover 用紫金过渡；选中国家用金色描边和轻微高度。
3. 国界描边仅在适合缩放和性能档位下呈现；不要以每国独立 DOM/SVG 绘制。
4. 只为 hover/selected 和参赛国显示少量标签/徽标，避免 200+ 文本标签遮挡和持续布局。
5. 使用一盏方向光加环境光/半球光及轻量大气 halo；避免高成本实时阴影贴图。

## 9. GeoJSON / TopoJSON 与国家映射方案

### 9.1 数据源选择

推荐以 Natural Earth **Admin 0 Map Units** 为原始边界来源：它比只含主权国家的 Admin 0 Countries 更适合英国四协会等“足球协会与 ISO 国家不一一对应”的需求。Natural Earth 的数据可公开使用，且其 Admin 0 Countries 文档说明 Map Units 可用于需要构成单元的场景。[Natural Earth 使用条款](https://www.naturalearthdata.com/about/terms-of-use/) [Admin 0 Countries / Map Units 说明](https://www.naturalearthdata.com/downloads/10m-cultural-vectors/10m-admin-0-countries/) [Natural Earth Map Units README（英国构成单元）](https://github.com/nvkelso/natural-earth-vector/blob/master/packages/Natural_Earth_quick_start/10m_cultural/ne_10m_admin_0_map_units.README.html)

建议的资源层级：

- 桌面默认：Natural Earth 1:50m Map Units，经本地简化和 TopoJSON 转换后发布。
- 手机/低性能回退：1:110m Map Units 或进一步简化版本。
- 高倍率/高质量（仅在真实需求确认后）：按需加载 1:10m 数据；原始 10m 国家数据体积较大，不应作为首次页面加载资源。
- `world-atlas` 可作为 ISO numeric ID 的通用低体积备用数据，但它基于 Natural Earth Admin 0 Countries，往往会把英国归为 `GBR`，不适合作为本需求的唯一主数据。[world-atlas README](https://github.com/topojson/world-atlas/blob/master/README.md)

不建议运行时从第三方 API 下载边界；应将许可合规、版本锁定后的衍生文件随站点发布并带版本号。所选 Natural Earth 版本、其属性字段（如 map unit 名称/代码/continent）以及是否有足够的海外属地/争议地区处理，需要在下载并验收原文件后标注为 **待确认**。

### 9.2 预处理产物与键

建议生成、版本控制以下静态文件（文件名仅为建议）：

```text
public/assets/data/globe/world-map-units-50m.topo.json
public/assets/data/globe/world-map-units-110m.topo.json
public/assets/data/globe/globe-country-map.v1.json
public/assets/data/globe/continent-membership.v1.json
```

- TopoJSON 用于传输与共享边界压缩；在浏览器端用 `topojson-client` 转成 GeoJSON Feature，再交给 globe.gl。
- `globe-country-map.v1.json` 是项目自有、经人工审核的映射层。每条至少有 `map_feature_id`、`geo_key`、`iso_a2`、`iso_a3`、`iso_numeric`、`fifa_team_code`、`display_unit_code`、`continent_key`、`geo_level`、`notes`。
- 以地图源稳定 feature ID + 项目自有 `geo_key` 关联，**禁止用中文名、英文名或模糊简称匹配**。
- `continent-membership` 由预处理脚本产出，包含每个大洲的中心点、初始相机高度及成员 feature ID；不在浏览器中根据显示名称推断。

globe.gl/three-globe 接收 GeoJSON 经纬度多边形并生成球面层；因此 v1 不需要自行把每个坐标转网格。若日后采用纯 Three.js，自定义球面点可按半径 `r`、纬度 `φ`、经度 `λ` 使用 `(r cosφ cosλ, r sinφ, r cosφ sinλ)`，但多边形环、洞、三角化与经线跨越处理不应手写为首选方案。

### 9.3 建议的数据模型调整

长期推荐把“地理国家”从当前的“有世界杯球队的国家资料”中拆出：

```text
geo_countries
  id, geo_key (唯一), iso_a2, iso_a3, iso_numeric,
  name_cn, name_en, capital, latitude, longitude,
  continent_key, map_feature_id, source_name, source_version

fifa_associations（或 geo_country_map）
  id, fifa_team_code, fifa_id, team_id（可空）, geo_country_id,
  map_feature_id, display_unit_code, is_fifa_association, notes

tournament_country_participation
  tournament_id, geo_country_id / fifa_association_id,
  participation_status, qualification_status, source_url, checked_at
```

其中 `geo_countries` 覆盖所有可点击地理实体，`fifa_associations` 解耦 FIFA 队/协会与地图单元，`tournament_country_participation` 才表达“已晋级/未晋级/未参赛/状态未知”。`country_profiles` 若保留，可以迁移为展示资料的兼容层；不能继续只由 `teams` 驱动。

如果希望初版改动最小，可先扩展 `country_profiles`（增加 ISO3、numeric、`map_feature_id`、`geo_level`、`is_participant`），并移除 `sync_country_profiles.php` 中“没有球队即删除”的行为。但这会持续混合地理国家与足球协会语义；长期不如拆表清晰。

## 10. 大洲→国家→球队详情交互方案

### 10.1 前端状态与流程

```text
页面加载
  └─ 载入轻量地图 + 国家映射 + 参赛队摘要
       └─ 渲染低饱和全图，参赛映射单元着金色

点击大洲（侧栏/浮层，而不是猜测地球空白点击）
  └─ 根据 continent-membership 取得中心坐标/相机高度
       └─ 相机平滑旋转、缩近，临时高亮成员国家
            └─ 点击/键盘选择国家 Polygon
                 └─ 由 map_feature_id → geo_key → FIFA 协会/teams 查询
                      ├─ 有本届参赛队：显示球队、球员、赛程、比分、积分和统计入口
                      └─ 无参赛队：显示国家资料 + “未参加/未晋级状态待数据确认”
```

建议大洲的选择入口为页面可访问的按钮/标签列表，地球上的 Polygon 点击作为补充。仅靠“点击地球上的大洲”会因跨洲国家、海岛、遮挡与小屏精度造成歧义。

### 10.2 关键前端行为

- 控件：允许鼠标拖拽/触摸旋转、滚轮或双指缩放；启用阻尼产生惯性；空闲时低速自动旋转；用户交互后暂停自动旋转，空闲若干秒恢复。
- 聚焦：根据预定义 continent/country camera preset 做插值，避免每次由包围盒实时计算造成跨日界线/多岛国家异常。选国后将其置于前半球且不必过度放大。
- 命中：只对地图 Feature 做 raycast；pointer move 节流，pointer up 确认点击，以避免拖拽误点。
- 可见性：选中/hover 刷新颜色和少量说明，不为每个国家创建 DOM label。
- 详情：初版可使用现有 `teams/<id>` 与 `matches/<id>` 链接；国家级抽屉/页面在有聚合查询后再加入。打开失败或 WebGL 不可用时保留当前国家卡片作为完整的键盘/无障碍降级。

### 10.3 后端查询建议

建议新增（非本次实施）一个国家聚合方法，例如 `AppRepository::countryGlobeDetail(string $geoKey)`，包含：

- 国家资料；
- 映射的 `teams`；
- 这些球队的 `players` 摘要；
- `matches` 中 `home_team_id` 或 `away_team_id` 命中的赛程/比分；
- 对应 `standings`、`match_stats`、`match_events` 的摘要/链接；
- 明确的 `participation_status` 和数据更新时间。

可暴露为服务端详情页或 `/api/globe/countries/<geoKey>` JSON。接口名为建议名称，当前项目没有此接口。聚合时必须基于映射表连接，不能直接以 `country_code` 对 `teams` 做一对一假设。

## 11. 预计新增/修改的主要文件（建议，尚未执行）

| 文件 | 动作 | 用途 |
|---|---|---|
| `views/countries.php` | 修改 | 放置真实 WebGL 容器、无障碍大洲控制和国家详情占位/降级卡片 |
| `public/assets/js/globe-3d.js` | 新增 | 独立管理 globe.gl 生命周期、交互、映射、相机和清理逻辑 |
| `public/assets/js/app.js` | 小范围修改 | 移除/停用旧 marker 伪球初始化，保留不相关交互 |
| `public/assets/css/app.css` | 修改 | 保留深蓝/紫/金视觉 token，增加 canvas、抽屉、降级和响应式样式 |
| `public/assets/data/globe/*.topo.json` | 新增（预处理产物） | 本地版世界地图的性能档位 |
| `public/assets/data/globe/*.json` | 新增 | 地图 feature 与地理/FIFA 键、大洲的显式映射 |
| `app/Repositories/AppRepository.php` | 修改 | 增加国家-球队-赛事的聚合查询 |
| `app/Controllers/PageController.php`、`controllers/SiteController.php`、`config/web.php` | 修改 | 新增国家详情和/或地球 JSON 路由 |
| `database/schema.sql` | 修改 | 全地理国家、FIFA 映射、届次参赛状态表/字段 |
| `scripts/sync_country_profiles.php` | 修改 | 不再删除非参赛国家，并转向全世界目录导入 |
| `scripts/import_collected_data.php`、`app/Services/WorldCupSyncService.php` | 修改 | 收敛英格兰、苏格兰、威尔士等国家码规范 |
| `scripts/build_globe_geo_data.*` | 新增 | 仅构建时下载/清洗/简化/验证地图数据；不在前端运行 |

## 12. 性能、兼容性与无障碍风险

| 风险 | 原因 | 建议控制 |
|---|---|---|
| 初次加载过重 | 10m 国界、多岛 Polygon、纹理与 JS 库叠加 | 默认 50m/110m TopoJSON，按设备选档；懒加载地球；本地缓存和压缩 |
| 手机 GPU/内存不足 | 高 DPR、几百个多边形、标签和连续渲染 | DPR 限制（例如移动端不高于 1.5），关闭阴影/高阶后处理，少标签，低精度数据 |
| 帧率/耗电 | 永久 `requestAnimationFrame`、pointer raycast、DOM 更新 | 仅在拖拽、镜头 tween 或自动旋转时渲染；页面隐藏时暂停；raycast 节流 |
| WebGL2 不可用或 context lost | 老旧浏览器、企业设备、移动内存回收 | feature detect、监听 context loss，降级为现有卡片/2D 地图，不能让国家导航失效 |
| 国界缝隙与跨日界线 | GeoJSON 复杂环、多边形简化、不同源拼接 | 单一版本化主数据源；预处理验证；避免临时混合多个边界源 |
| 标签重叠/点击歧义 | 小国家、岛屿、跨洲国家、旋转状态 | 仅显示选中/hover 标签；大洲用明确按钮；小国家支持搜索与卡片列表 |
| 无障碍 | WebGL canvas 本身缺少语义和键盘地图可达性 | 保留国家搜索/列表、可键盘操作的大洲按钮与球队链接，提供文字状态 |
| 减少动态效果偏好 | 自动旋转/动画可能造成不适 | 遵循 `prefers-reduced-motion`，关闭自动旋转并缩短/跳过镜头动画 |

## 13. 实现难点与需先确认事项

1. **数据真实性优先于渲染。** 当前本地世界杯表为空；应先确认并成功导入完整采集包，再验收球队数、赛程、比分和统计覆盖率。
2. **英国四协会映射冲突。** `import_collected_data.php` 与 `WorldCupSyncService.php` 对 ENG/SCO/WAL 的 `country_code` 规则不一致；实施前必须指定唯一规范键并迁移/兼容旧数据。
3. **“未参加”与“未晋级”不可从当前表准确推出。** 没有资格赛、报名、退出/禁赛或资格状态字段。无球队只能安全表述为“当前世界杯数据中没有关联参赛队”；精确文案需新增状态数据与来源。
4. **国家粒度的产品定义。** “所有国家”是否包括海外属地、事实独立地区、争议地区及北爱尔兰等，需要产品/法务明确。不要让地图源默认边界悄然成为业务政策。
5. **地图源字段验收。** 已推荐 Natural Earth Map Units，但最终 source version、稳定 feature 属性、简化阈值、许可证说明和属性映射仍需在实际下载预处理后验证。
6. **视觉与数据一致。** 参赛“国家”有时是足球协会而不是主权国家，UI 应显示“FIFA 代表队/协会”或按具体队名显示，避免把 ENG、SCO 误标为 ISO 主权国家。

## 14. 建议的分阶段实施顺序

1. **数据基线与规范确认**：恢复/确认完整导入包，导入到测试库；核验 `teams`、`players`、`matches`、`standings`、`country_profiles` 行数与关键关联；确定唯一 `geo_key` 和英国四协会映射规则。验证标准：每支参赛队均能映射到一个地图单元，未出现按名称兜底。
2. **离线地理数据管道**：选定 Natural Earth Map Units 版本，生成 50m/110m TopoJSON 和映射清单；对 feature 数、孤立岛屿、英国四协会、跨日界线做自动/人工验收。验证标准：所有可点击地图单元有稳定 ID，映射校验报告无未解释的参赛队。
3. **3D 最小可用地球**：在隔离容器接入本地 globe.gl，渲染真实边界、拖拽、惯性、灯光、自动旋转及 WebGL 降级。验证标准：桌面和手机测试设备可流畅旋转，无 WebGL 时仍可通过国家卡片访问内容。
4. **参赛队高亮与大洲聚焦**：加入大洲按钮、镜头预设、参赛国颜色、hover/selected 状态。验证标准：点击六大洲后相机正确聚焦；英国四协会的视觉和数据映射正确。
5. **国家详情聚合**：新增国家级查询/接口或页面，展示球队、球员、赛程、比分、积分和比赛详情入口；非参赛国展示经确认的状态文案。验证标准：每个参赛队从地球可到球队/比赛详情，非参赛国不会伪造赛事数据。
6. **性能、可访问性和视觉打磨**：设备分级、资源懒加载、降级、键盘路径、减少动态效果、视觉主题和观测指标。验证标准：目标 PC/手机实测帧率、内存、首次加载、键盘流程和 context-loss 回退均达到预先定义阈值。

## 15. 最终建议

不要在现有 SVG 上继续叠加路径或试图把 CSS 伪球“补成”3D；那会持续缺失真实边界、可靠点击和性能控制。先解决“全世界地理单元 vs FIFA 队/协会”的规范与导入数据，再以 **globe.gl + 预处理 Natural Earth Map Units + 显式映射表**替换当前页面中的伪地球。这个顺序能最小化对既有 Yii 页面、球队页和比赛页的侵入，同时为后续全世界可点、世界杯参赛高亮和国家赛事详情留出可靠的数据基础。
