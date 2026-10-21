<?php
/** Accessible scoreboard partial; null scores are never converted to zero. */
$home=$model->homeTeam?->name?:($model->home_team_name?:'待定'); $away=$model->awayTeam?->name?:($model->away_team_name?:'待定');
?>
<a class="match-tile" href="/matches/<?= (int)$model->id ?>"><div class="meta"><span><?= e($model->stage) ?></span><span><?= e(['finished'=>'已结束','scheduled'=>'赛程','live'=>'进行中','postponed'=>'延期','cancelled'=>'取消','unknown'=>'待确认'][$model->status]??$model->status) ?></span></div><div class="score-team"><span><?= \app\services\TeamUi::label($model->homeTeam) ?></span><b><?= $model->home_score===null?'—':(int)$model->home_score ?></b></div><div class="score-team"><span><?= \app\services\TeamUi::label($model->awayTeam) ?></span><b><?= $model->away_score===null?'—':(int)$model->away_score ?></b></div><div class="meta"><time><?= e($model->starts_at?date('m/d H:i',strtotime($model->starts_at)):'时间待定') ?></time><span>比赛详情 ↗</span></div></a>
