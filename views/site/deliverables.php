<?php
/** Course-required downloads are visible only after real files are registered. */
$this->title='项目与课程交付';
?>
<section class="page-heading"><h1>课程作业下载</h1><p>团队与个人作业统一登记，真实文件审核后提供下载。</p></section><div class="standing-grid"><?php foreach(['team'=>'团队作业','personal'=>'个人作业'] as $scope=>$label): ?><section class="form-panel"><h2><?= e($label) ?></h2><?php $n=0; foreach($files as $file): if($file->scope!==$scope) {continue;} $n++; ?><a class="bookmark-item" href="/downloads/<?= (int)$file->id ?>"><?= e($file->title) ?> ↓</a><?php endforeach ?><?php if(!$n): ?><p>此类作业尚未发布。</p><?php endif ?></section><?php endforeach ?></div><section class="section prose"><h2>课程交付清单</h2><p>团队：需求文档、设计文档、实现文档、用户手册、部署文档、展示 PPT、5–15 分钟录屏。</p><p>个人：Web 前端初探、Web 前端设计、开源建站工具初试。</p><p>文件命名与截止时间以老师课件及课程通知为准；当前交付状态见各文件说明。</p></section>
