<?php
/** One competition centre, one standings entry; no duplicate top-level data module. */
$tabs=['fixtures'=>['/worldcup','赛程与晋级'],'standings'=>['/worldcup/standings','小组积分'],'videos'=>['/watch','比赛回放']];
?>
<nav class="tab-bar competition-nav" aria-label="赛事中心"><?php foreach($tabs as $key=>[$path,$label]): ?><a href="<?= e($path) ?>" <?= $key===$active?'class="active" aria-current="page"':'' ?>><?= e($label) ?></a><?php endforeach ?></nav>
