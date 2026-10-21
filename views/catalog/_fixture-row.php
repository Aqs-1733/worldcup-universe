<?php
/** Compact fixture line, used inside a labelled group rather than an unstructured wall. */
use app\services\TeamUi;
?>
<a class="fixture-row" href="/matches/<?= (int)$model->id ?>"><time datetime="<?= e(str_replace(' ','T',$model->starts_at).'Z') ?>"><?= e(date('m.d',strtotime($model->starts_at))) ?><small><?= e(date('H:i',strtotime($model->starts_at))) ?></small></time><span class="fixture-home"><?= TeamUi::label($model->homeTeam) ?></span><b class="fixture-score"><?= e($model->home_score??'—') ?><i>:</i><?= e($model->away_score??'—') ?><?php if($model->home_penalty_score!==null): ?><small>点球 <?= (int)$model->home_penalty_score ?> : <?= (int)$model->away_penalty_score ?></small><?php endif ?></b><span class="fixture-away"><?= TeamUi::label($model->awayTeam) ?></span><span class="fixture-arrow" aria-hidden="true">↗</span></a>
