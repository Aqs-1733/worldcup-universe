<?php
/**
 * 文件作用：团队页（/team）的静态配置——团队名称、成员分工、每人负责的 MVC 文件、团队作业清单和截止日期。
 * 成员姓名、学号、角色保存在数据库 team_members 表；这里按学号对应分工，内容与《实现文档》“成员分工”一节保持一致。
 * 作者：蒋林瀞_2413507
 */
return [
 'name'=>'GeminaiLong',
 'tagline'=>'2026 世界杯资料与球迷社区 · 课程团队项目',
 // 负责内容：按学号对应
 'duties'=>[
  '2413507'=>['项目统筹与进度','后台管理与内容审核','球迷论坛','界面重构与动效','服务器部署、自动更新与备份'],
  '2412096'=>['界面原型与视觉规范','账号注册登录','个人中心与回复通知','数据库全套设计'],
  '2412439'=>['前台信息架构与导航','首页、球队与球员页面','国家探索与 3D 地球'],
  '2412852'=>['赛事内容模块','官方数据采集与导入','赛程、积分与晋级对阵图','新闻同步'],
 ],
 // 成员卡片上的简短标签（每人两行以内）；完整描述见上面的 duties
 'tags'=>[
  '2413507'=>['项目统筹','后台与论坛','界面重构与动效','部署运维'],
  '2412096'=>['界面原型','注册登录','个人中心','数据库设计'],
  '2412439'=>['前台架构与导航','首页','球队与球员页','3D 地球'],
  '2412852'=>['赛事内容','官方数据采集','赛程与积分','新闻同步'],
 ],
 // 每人负责的模型、视图、控制器文件（课程要求每人至少 MVC 各一个）
 'mvc'=>[
  '2413507'=>['M'=>'models/AuditLog.php、ForumThread.php','V'=>'views/admin-v2/index.php、views/forum/view.php','C'=>'controllers/AdminController.php、ForumController.php'],
  '2412096'=>['M'=>'models/FanProfile.php','V'=>'views/profile/index.php','C'=>'controllers/ProfileController.php'],
  '2412439'=>['M'=>'models/Team.php','V'=>'views/catalog/teams.php、team.php','C'=>'controllers/TeamController.php'],
  '2412852'=>['M'=>'models/MatchGame.php','V'=>'views/catalog/matches.php、match.php','C'=>'controllers/MatchController.php'],
 ],
 // 团队作业清单：data/team/ 里文件名包含 match 关键字时显示为“已上传”
 'deliverables'=>[
  ['title'=>'需求文档','match'=>'需求文档'],
  ['title'=>'设计文档','match'=>'设计文档'],
  ['title'=>'实现文档','match'=>'实现文档'],
  ['title'=>'用户手册','match'=>'用户手册'],
  ['title'=>'部署文档','match'=>'部署文档'],
  ['title'=>'项目展示 PPT','match'=>'项目展示'],
  ['title'=>'录屏讲解','match'=>'录屏讲解'],
 ],
 'deadline'=>'2026-12-01',
];
