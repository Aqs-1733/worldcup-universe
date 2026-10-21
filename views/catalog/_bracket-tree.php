<?php
/** Progressive native-details tree follows the verified winner edges, not visual row order. */
$byId=[];$incoming=[];$final=null;$bronze=null;
foreach($matches as $game){$byId[(int)$game->id]=$game;if($game->stage==='决赛')$final=$game;if($game->stage==='季军赛')$bronze=$game;}
foreach($links as $edge){if($edge['outcome']==='winner')$incoming[(int)$edge['to_match_id']][]=(int)$edge['from_match_id'];}
?>
<section class="section knockout-section" id="bracket" data-bracket-tree>
 <div class="section-head"><h2>淘汰赛对阵</h2><div class="tree-actions"><button type="button" data-tree-reset>收起路线</button></div></div>
 <div class="tree-toolbar"><label>查看球队路线<select data-team-select data-bracket-team><option value="">全部球队</option><?php foreach($teams as $team): ?><option value="<?= (int)$team->id ?>" data-flag="<?= e(\app\services\TeamUi::url($team)) ?>"><?= e($team->name) ?></option><?php endforeach ?></select></label><span data-tree-status aria-live="polite">点击对阵展开前一轮</span></div>
 <div class="knockout-forest"><?php if($final): ?><div class="championship-tree"><?= $this->render('_bracket-branch',['id'=>(int)$final->id,'byId'=>$byId,'incoming'=>$incoming,'depth'=>0,'seen'=>[]]) ?></div><?php endif ?>
 <?php if($bronze): ?><aside class="bronze-tree"><?= $this->render('_bracket-branch',['id'=>(int)$bronze->id,'byId'=>$byId,'incoming'=>[],'depth'=>0,'seen'=>[]]) ?><div class="bronze-origin"><span>半决赛落败球队进入季军赛</span><a href="https://api.fifa.com/api/v3/calendar/matches?idCompetition=17&amp;idSeason=285023&amp;language=en&amp;count=500" target="_blank" rel="noopener">官方赛程来源 ↗</a></div></aside><?php endif ?></div>
</section>
