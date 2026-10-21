<?php
/** Each match occurs once in the championship ancestry; leaf links remain accessible. */
if(!isset($byId[$id])||isset($seen[$id])||$depth>6)return;
$seen[$id]=true;$game=$byId[$id];$children=$incoming[$id]??[];
?>
<details class="tie-branch" data-match="<?= (int)$id ?>" data-home="<?= (int)$game->home_team_id ?>" data-away="<?= (int)$game->away_team_id ?>" <?= $depth===0&&$children?'open':'' ?>>
 <summary class="bracket-node tie-summary"><span class="tie-stage"><b><?= e($game->stage) ?></b><small>#<?= (int)$game->match_number ?> · <?= e(date('m.d H:i',strtotime($game->starts_at))) ?></small></span><span class="tie-teams"><span class="tie-team"><?= \app\services\TeamUi::label($game->homeTeam) ?><strong><?= e($game->home_score??'—') ?></strong></span><span class="tie-team"><?= \app\services\TeamUi::label($game->awayTeam) ?><strong><?= e($game->away_score??'—') ?></strong></span></span><span class="tie-toggle" aria-hidden="true">＋</span><?php if($game->home_penalty_score!==null): ?><em class="tie-penalties">点球 <?= (int)$game->home_penalty_score ?> : <?= (int)$game->away_penalty_score ?></em><?php endif ?></summary>
 <div class="tie-expanded"><div class="tie-tools"><span><?= $children?'晋级来源':'本场比赛' ?></span><a href="/matches/<?= (int)$game->id ?>">比赛详情 ↗</a></div><?php if($children): ?><ol class="tie-children"><?php foreach($children as $child): ?><li><?= $this->render('_bracket-branch',['id'=>$child,'byId'=>$byId,'incoming'=>$incoming,'depth'=>$depth+1,'seen'=>$seen]) ?></li><?php endforeach ?></ol><?php endif ?></div>
</details>
