<?php
/**
 * 文件作用：局部视图：赛程列表中的一行比赛。
 * 作者：梁靖泽_2412852
 */
use app\services\TeamUi;
?>
<a class="fixture-row" href="/matches/<?= (int)$model->id ?>"><time datetime="<?= e(str_replace(' ','T',$model->starts_at).'Z') ?>"><?= e(date('m.d',strtotime($model->starts_at))) ?><small><?= e(date('H:i',strtotime($model->starts_at))) ?></small></time><span class="fixture-home"><?= TeamUi::label($model->homeTeam) ?></span><b class="fixture-score"><?= e($model->home_score??'—') ?><i>:</i><?= e($model->away_score??'—') ?><?php if($model->home_penalty_score!==null): ?><small>点球 <?= (int)$model->home_penalty_score ?> : <?= (int)$model->away_penalty_score ?></small><?php endif ?></b><span class="fixture-away"><?= TeamUi::label($model->awayTeam) ?></span><span class="fixture-arrow" aria-hidden="true">↗</span></a>
