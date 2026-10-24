<?php
/** One real team's knockout journey. Connectors require explicit verified progression edges. */
use yii\helpers\Json;
$games=[];$rounds=[];$teamIds=[];$championId=null;
foreach($matches as $game){
 $games[]=['id'=>(int)$game->id,'home'=>(int)$game->home_team_id,'away'=>(int)$game->away_team_id,'stage'=>$game->stage];
 $teamIds[]=(int)$game->home_team_id;$teamIds[]=(int)$game->away_team_id;
 if($game->stage!=='季军赛'&&!in_array($game->stage,$rounds,true))$rounds[]=$game->stage;
 if($game->stage==='决赛'&&$game->winner_fifa_id){foreach([$game->homeTeam,$game->awayTeam] as $team){if($team&&$team->fifa_id===$game->winner_fifa_id)$championId=(int)$team->id;}}
}
$selected=$championId??($teamIds[0]??null);
?>
<section class="edition-journey" data-bracket-route data-default-team="<?= (int)$selected ?>">
 <div class="edition-journey-heading"><div><span class="edition-kicker">ROAD TO THE FINAL</span><h2>每一场，离梦想更近。</h2></div><span>2026 · 淘汰赛</span></div>
 <div class="edition-route-toolbar"><label for="journey-team">选择球队，沿途回看<select id="journey-team" data-team-select data-route-team><?php foreach($teams as $team): ?><?php if(in_array((int)$team->id,$teamIds,true)): ?><option value="<?= (int)$team->id ?>" data-flag="<?= e(\app\services\TeamUi::url($team)) ?>" <?= (int)$team->id===$selected?'selected':'' ?>><?= e($team->name) ?></option><?php endif ?><?php endforeach ?></select></label><p data-route-status aria-live="polite">横向探索真实晋级路线 · 点击比赛查看详情</p><div class="edition-route-scroll-controls"><button type="button" data-route-scroll="-1" aria-label="向前查看轮次">←</button><button type="button" data-route-scroll="1" aria-label="向后查看轮次">→</button></div></div>
 <div class="edition-route-scroll" tabindex="0" aria-label="可横向滚动的淘汰赛路线"><div class="edition-route-track" data-route-track>
 <?php foreach($rounds as $round): ?><div class="edition-route-round"><span class="edition-round-label"><?= e($round) ?></span><?php $found=false;foreach($matches as $game): ?><?php if($game->stage===$round&&in_array($selected,[(int)$game->home_team_id,(int)$game->away_team_id],true)): $found=true; ?><?= $this->render('_route-match',['game'=>$game]) ?><?php endif ?><?php endforeach ?><?php if(!$found): ?><div class="edition-route-empty">没有本轮比赛</div><?php endif ?></div><?php endforeach ?>
 </div></div>
 <div class="edition-route-footnote"><span>路线依据已核验的晋级关系 · 比分为采集快照</span><a href="#bracket-map">回到淘汰赛全景图 ↑</a></div>
 <script type="application/json" data-route-data><?= Json::htmlEncode(['matches'=>$games,'rounds'=>$rounds,'edges'=>$links]) ?></script>
 <?php foreach($matches as $game): ?><template data-route-template="<?= (int)$game->id ?>"><?= $this->render('_route-match',['game'=>$game]) ?></template><?php endforeach ?>
</section>
