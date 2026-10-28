<?php
/**
 * Team page content that is not stored in the database. Member names, numbers and roles come from team_members.
 * duties: keyed by student number; the starting labels are the team's own plan from the coursework prototype — edit freely.
 * deliverables: 'match' is a keyword; once a file whose name contains it is in data/team/, the item shows as uploaded.
 */
return [
 'project'=>'世界杯宇宙',
 'tagline'=>'2026 世界杯资料与球迷社区 · 课程团队项目',
 'duties'=>[
  '2413507'=>['统筹与进度','后台管理模块'],
  '2412439'=>['前台信息架构'],
  '2412852'=>['赛事内容模块'],
  '2412096'=>['界面原型与报告'],
 ],
 'deliverables'=>[
  ['title'=>'需求文档','match'=>'需求'],
  ['title'=>'设计文档','match'=>'设计'],
  ['title'=>'实现文档','match'=>'实现'],
  ['title'=>'用户手册','match'=>'用户手册'],
  ['title'=>'部署文档','match'=>'部署'],
  ['title'=>'展示 PPT','match'=>'PPT'],
  ['title'=>'讲解录屏','match'=>'录屏'],
 ],
 'deadline'=>'2026-12-01',
];
