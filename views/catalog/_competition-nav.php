<?php
/**
 * 文件作用：局部视图：赛事中心的子导航（赛程与晋级、小组积分、比赛回放）。
 * 作者：梁靖泽_2412852
 */
$tabs=['fixtures'=>['/worldcup','赛程与晋级'],'standings'=>['/worldcup/standings','小组积分'],'videos'=>['/watch','比赛回放']];
?>
<nav class="tab-bar competition-nav" aria-label="赛事中心"><?php foreach($tabs as $key=>[$path,$label]): ?><a href="<?= e($path) ?>" <?= $key===$active?'class="active" aria-current="page"':'' ?>><?= e($label) ?></a><?php endforeach ?></nav>
