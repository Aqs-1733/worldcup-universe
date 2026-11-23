<?php
/**
 * 文件作用：视图：球队详情页，显示球队资料、阵容名单、比赛记录和讨论。
 * 作者：崔迪_2412439
 */
$this->title=$model->name;
?>
<?php /* 页头：球队名、国旗、合照，以及“设为主队”入口 */ ?>
<a class="back-link" href="/teams">← 所有球队</a><section class="profile-hero nation-hero"><div><span class="eyebrow"><?= e($model->confederation) ?> / <?= e($model->code) ?></span><h1><?= \app\services\TeamUi::label($model) ?></h1><p><?= e($model->name_original) ?></p><a class="button" href="/profile?team=<?= (int)$model->id ?>">设为我的主队</a><a class="text-link" href="/forum?team_id=<?= (int)$model->id ?>">球队论坛 →</a></div><?php $teamPhoto=app\services\TeamPhoto::get($model->code); ?><?php if($teamPhoto): ?><img class="team-squad-hero" src="<?= e($teamPhoto['path']) ?>" alt="<?= e($model->name) ?> · 2026 世界杯球队合照"><?php endif ?><span class="hero-flag" aria-hidden="true"><?= \app\services\TeamUi::flag($model) ?></span></section>
<?php /* 基本信息：主教练、比赛数、小组、阵容人数 */ ?>
<?= $this->render('_source',['model'=>$model]) ?><div class="facts-grid"><div><small>主教练</small><b><?= e($model->coach_name?:'待同步') ?></b></div><div><small>本届比赛</small><b><?= count($matches) ?></b></div><div><small>小组</small><b><?= e($model->group_name?:'待确认') ?></b></div><div><small>阵容人数</small><b><?= count($players) ?></b></div></div>
<?php if($model->profile): ?><section class="section prose"><h2>球队档案</h2><p><?= nl2br(e($model->profile)) ?></p></section><?php endif ?>
<?php /* 球队阵容（按球衣号码排序） */ ?>
<section class="section"><div class="section-head"><h2>球队阵容</h2></div><div class="player-grid"><?php foreach($players as $p): ?><?= $this->render('_player',['model'=>$p]) ?><?php endforeach ?></div><?php if(!$players): ?><div class="empty">阵容资料尚未采集。</div><?php endif ?></section>
<?php /* 本队全部比赛 */ ?>
<section class="section"><h2>球队赛程</h2><div class="match-strip"><?php foreach($matches as $m): ?><?= $this->render('_match',['model'=>$m]) ?><?php endforeach ?></div></section>
<?= $this->render('_comments',['items'=>$comments,'type'=>'team','id'=>$model->id]) ?>
