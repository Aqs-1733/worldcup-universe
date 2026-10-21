# 2026 世界杯完整回放核验

核验日期：2026-10-21（北京时间）。只接受 2026 男足世界杯决赛圈完整比赛，不以旧年回放、预选赛、节目片段或集锦替代。

## 结论

已核实一项 2026 完整比赛的官方资源，以及该资源由官方页面公开声明的 iframe 地址。实际视频画面尚未在本地浏览器完成播放验收；页面上线应保留官方观看入口，由官方播放器处理登录与播放状态。当前未核验到 FIFA 官方 YouTube 频道上满足本次条件的 2026 完整比赛 ID；这是本次有限检索结果，不代表全网不存在。

## 可接入候选：RTVE 2026 决赛

| 字段 | 核验结果 |
| --- | --- |
| 比赛 | 西班牙—阿根廷，2026 世界杯决赛 |
| 发布者 | RTVE（西班牙公共广播电视机构） |
| 视频 ID | `17162172` |
| 类型 | 官方 API 的 `type.name=Completo`；标题、路径与节目分类均对应 2026 决赛 |
| 时长 | `13,360,650 ms`，约 3 小时 42 分 40 秒，含转播节目 |
| 发布 / 到期 | 2026-07-20 / 2027-01-15（官方页面时间） |
| 嵌入文档 | HTTP 200；本次响应未见 X-Frame-Options 或 CSP frame-ancestors 拦截 |
| 官方声明 | 页面 JSON-LD 的 `VideoObject.embedUrl` 指向下列官方 iframe 地址 |
| 播放条件 | API 返回 `requireLogged=true`、`hasDRM=true`、`notDownloadable=true`；不提取媒体流、不代绕登录 |
| 播放验收 | 隐藏浏览器导航超时，尚未观察到视频帧；不得标为“已播放验证” |

- [官方完整比赛页面](https://www.rtve.es/play/videos/copa-mundial-de-la-fifa-2026/final-espana-argentina-completo-mundial-2026/17162172/)
- [官方元数据 API](https://www.rtve.es/api/videos/17162172.json)
- [官方 iframe 页面](https://www.rtve.es/drmn/embed/video/17162172/)

```html
<iframe
  src="https://www.rtve.es/drmn/embed/video/17162172/"
  title="2026 世界杯决赛：西班牙对阿根廷"
  allow="autoplay; fullscreen; encrypted-media; picture-in-picture"
  referrerpolicy="strict-origin-when-cross-origin"
  allowfullscreen loading="lazy"></iframe>
```

元数据摘录保存在 `data/video-evidence/rtve-2026-final.json`。它保留真实布尔状态，不把 HTTP 200 等同于播放成功。

## 官方外链：SBS 全场目录

[SBS 2026 Full Matches](https://www.sbs.com.au/ondemand/sports-series/fifa-world-cup-2026/fifa-world-cup-2026-full-matches) 明确分类为完整比赛，目录列出全部 104 场；另附一项纯比赛决赛版本。已核验的决赛纯比赛 ID 为 `2506805315863`，时长约 2 小时 40 分。其播放页本次响应带 `X-Frame-Options: SAMEORIGIN`，因此仅列官方观看外链，不直接把该网页塞进本站 iframe。

[决赛官方观看页](https://www.sbs.com.au/ondemand/sports-series/fifa-world-cup-2026/season-2026/fifa-world-cup-2026-s2026-ep105/2506805315863)

## FIFA 入口与排除项

- [FIFA 2026 官方视频页](https://www.fifa.com/tournaments/mens/worldcup/canadamexicousa2026/highlights)：已见比赛集锦条目，集锦不作为完整比赛。
- [FIFA+ 官方档案](https://www.plus.fifa.com/en/catalogue/archive)：可作官方查找入口；本次未从这里核验到符合条件的 2026 男足决赛圈完整回放及嵌入 ID。
- 先前核验的 2022、2018、2014 FIFA YouTube 视频不进入本项目的 2026 比赛回放清单。
- RTVE 的 `17163109` 虽标为 Completo，但标题为 Resumen，属于摘要节目，不作为完整比赛；类型字段必须与赛事、标题和时长交叉核对。

## 核验方式

仅请求官方 HTML 与 JSON 元数据；没有下载视频、获取 DRM 密钥、重放受保护 token 或制作伪造视频 ID。浏览器实际播放必须另外验收，后台应把 `metadata_verified` 与 `playback_verified` 分开记录。

2026-10-22 浏览器实测：官方 iframe 标题和封面已加载；点击播放后，RTVE 返回所在地播放权限制（“Este contenido tiene restringidos los derechos de emisión en su ubicación”）。本站显示官方登录／地区限制提示，未播放成功，不绕过地区或 DRM 校验。
