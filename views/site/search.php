<?php
/** Unified search results use the same bounded native query services. */
$this->title='搜索';
?>
<section class="page-heading"><h1>全站搜索</h1></section><form class="filter-bar" method="get"><label>关键词<input name="q" value="<?= e($q) ?>" placeholder="球队、球员、新闻" maxlength="120"></label><button class="button">搜索 →</button></form><?php foreach(['球队'=>$teams,'球员'=>$players,'新闻'=>$news] as $heading=>$items): ?><section class="section"><h2><?= e($heading) ?></h2><?php foreach($items as $m): ?><?php $href=match($heading){'球队'=>'/teams/'.$m->slug,'球员'=>'/players/'.$m->slug,'新闻'=>'/news/'.$m->id}; ?><a class="bookmark-item" href="<?= e($href) ?>"><?php if($heading==='球队'): ?><?= \app\services\TeamUi::label($m) ?><?php elseif($heading==='球员'): ?><?= \app\services\TeamUi::flag($m->team) ?> <?= e($m->name) ?><?php else: ?><?= e($m->title) ?><?php endif ?> ↗</a><?php endforeach ?><?php if(!$items): ?><p class="empty"><?= $q?'未找到相关内容。':'输入关键词开始查找。' ?></p><?php endif ?></section><?php endforeach ?>
