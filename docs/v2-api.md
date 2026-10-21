# 原生 Yii2 接口

接口前缀 `/api/v1`，JSON UTF-8。接口与网页共用 ActiveRecord、CatalogService、ForumService、PreferenceService；不是另一套数据。

## 查询

方法 | 路径 | 内容
---|---|---
GET | `/session` | 当前用户、CSRF 参数／令牌、管理员状态
GET | `/home` | 首页公开聚合、已署名首页图片
GET | `/teams`、`/teams/{id或slug}` | 官方球队、球队阵容
GET | `/players`、`/players/{id或slug}` | 官方球员、所属国家队
GET | `/matches`、`/matches/{id}` | 官方赛程、比分、双方阵容；未核验事件／统计不推算
GET | `/standings` | 官方比赛比分计算的小组积分
GET | `/bracket` | 已核验晋级关系，包含双方球队和国旗
GET | `/stats` | 兼容旧客户端的积分／射手接口；未采集射手数据为空数组
GET | `/news` | 已发布来源新闻，支持 q、source、tag
GET | `/countries` | 国家资料
GET | `/forum`、`/forum/{id}` | 可见主题、可见回复
GET | `/profile` | 登录用户、主队资料、未读数

列表返回 `{"data":[],"pagination":{"total":0,"page":1,"pageSize":24}}`；详情返回 `{"data":{}}`。page 从 1 开始。未知记录 404；数组查询参数 400。前台旧 `/stats` 为 301 到 `/worldcup/standings`，JSON `/api/v1/stats` 仍兼容。

## 写入与权限

登录／注册使用 `/login`、`/register` 原生表单和 Yii Session Cookie。所有写请求均保留 CSRF；未认证 401 或表单登录跳转，非所有者 403，模型校验失败 422，成功创建主题 201。

方法 | 路径 | 字段
---|---|---
POST | `/forum` | title、body、category_id，可选 team_id、match_id、tags
PATCH | `/forum/{id}` | 同上，仅作者／管理员
DELETE | `/forum/{id}` | 软删除，仅作者／管理员
POST | `/profile` | display_name、bio、main_team_id、follow_team_ids、follow_player_ids、block_team_ids

资料更新仅修改传入字段；一人最多一支主队，关注不与屏蔽重叠。不接受 role、password_hash、user_id 等越权字段。

原生网页还提供回复、引用、编辑、删除、幂等点赞／收藏、举报、回复通知；管理台提供内容审核、用户状态和白名单 CRUD。具体端点以 `config/web.php` 与控制器为准。

## 前端可复用代码

下例在本站已登录会话中发布主题，不把令牌写死在代码中：

```javascript
const { data: session } = await fetch('/api/v1/session', {
  credentials: 'same-origin'
}).then(r => r.json());
const response = await fetch('/api/v1/forum', {
  method: 'POST', credentials: 'same-origin',
  headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': session.csrfToken },
  body: JSON.stringify({ title: '比赛讨论', body: '你如何看待本场的阵容安排？', category_id: 1 })
});
const result = await response.json();
if (!response.ok) throw new Error(JSON.stringify(result));
```

新闻页 `/news/status` 只读提供采集状态；管理员 POST `/news/refresh` 才触发采集。抓取不在匿名页面请求内执行。
