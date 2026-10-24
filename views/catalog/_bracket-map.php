<?php
/** Symmetric knockout map. Layout follows the verified winner edges back from the final, so every connector is a real progression. */
use app\services\{ImmersiveService,TeamUi};
use yii\helpers\Json;
$byId=[];$incoming=[];$final=null;$bronze=null;
foreach($matches as $game){$byId[(int)$game->id]=$game;if($game->stage==='决赛')$final=$game;if($game->stage==='季军赛')$bronze=$game;}
foreach($links as $edge){if($edge['outcome']==='winner'&&isset($byId[(int)$edge['from_match_id']]))$incoming[(int)$edge['to_match_id']][]=(int)$edge['from_match_id'];}
foreach($incoming as &$list){usort($list,static fn($a,$b)=>(int)$byId[$a]->match_number<=>(int)$byId[$b]->match_number);}unset($list);
if(!$final||count($incoming[(int)$final->id]??[])!==2)return;
// Depth-first walk keeps each pair of feeder matches adjacent, which is what makes the columns line up.
$columns=static function(int $root) use($incoming): array{$cols=[];$walk=function(int $id,int $depth) use(&$walk,&$cols,$incoming){$cols[$depth][]=$id;foreach($incoming[$id]??[] as $child)$walk($child,$depth+1);};$walk($root,0);return $cols;};
[$leftRoot,$rightRoot]=$incoming[(int)$final->id];
$left=array_reverse($columns($leftRoot));$right=$columns($rightRoot);
$roundNames=['32强赛'=>'R32','16强赛'=>'R16','四分之一决赛'=>'QF','半决赛'=>'SF'];
$teamsInMap=[];$reach=[];$order=['32强赛'=>1,'16强赛'=>2,'四分之一决赛'=>3,'半决赛'=>4,'决赛'=>5];
foreach($byId as $g){foreach([$g->homeTeam,$g->awayTeam] as $t){if(!$t)continue;$teamsInMap[(int)$t->id]=$t;$reach[(int)$t->id]=max($reach[(int)$t->id]??0,$order[$g->stage]??0);}}
$winnerSide=static fn($g)=>ImmersiveService::winnerSide($g);
$championId=null;$w=$winnerSide($final);if($w)$championId=(int)($w==='home'?$final->home_team_id:$final->away_team_id);
uasort($teamsInMap,static fn($a,$b)=>[$reach[(int)$b->id],$a->name_original]<=>[$reach[(int)$a->id],$b->name_original]);
$card=static function($g,string $class='') use($winnerSide): string{
 $w=$winnerSide($g);$row=static function($team,$score,$pen,bool $win,bool $lose){
  return '<span class="bm-team'.($win?' is-win':'').($lose?' is-lose':'').'" data-team="'.(int)$team?->id.'">'.TeamUi::flag($team).'<span class="bm-code">'.e($team?->code?:'TBD').'</span><span class="bm-name">'.e($team?->name?:'待定').'</span><b>'.e($score??'–').($pen!==null?'<small>('.(int)$pen.')</small>':'').'</b></span>';
 };
 return '<a class="bm-match '.$class.'" href="/matches/'.(int)$g->id.'" data-bm-match="'.(int)$g->id.'" data-teams="'.(int)$g->home_team_id.' '.(int)$g->away_team_id.'" aria-label="'.e($g->stage.'：'.($g->homeTeam?->name?:'待定').' '.($g->home_score??'').' 比 '.($g->away_score??'').' '.($g->awayTeam?->name?:'待定')).'">'
  .'<span class="bm-meta">#'.(int)$g->match_number.' · '.e(date('m.d',strtotime($g->starts_at))).'</span>'
  .$row($g->homeTeam,$g->home_score,$g->home_penalty_score,$w==='home',$w==='away')
  .$row($g->awayTeam,$g->away_score,$g->away_penalty_score,$w==='away',$w==='home').'</a>';
};
$column=static function(array $ids,string $side,int $index) use($byId,$card,$roundNames): string{
 $stage=$byId[$ids[0]]->stage;$html='<div class="bm-col bm-col-'.$side.'" data-bm-col="'.$side.'-'.$index.'"><span class="bm-round">'.e($stage).'<i>'.e($roundNames[$stage]??'').'</i></span><div class="bm-slots">';
 foreach($ids as $id)$html.='<div class="bm-slot">'.$card($byId[$id]).'</div>';
 return $html.'</div></div>';
};
?>
<section class="bracket-map" id="bracket-map" data-bracket-map aria-labelledby="bracket-map-title">
 <div class="bm-head">
  <div><span class="edition-kicker">ROAD TO NEW YORK · NEW JERSEY</span><h2 id="bracket-map-title">淘汰赛全景图</h2><p>32 支球队，31 场淘汰赛，最终只有一支球队登顶。悬停或点击任意球队，点亮它的晋级之路。</p></div>
  <div class="bm-summary" data-bm-summary aria-live="polite"><span>选择一支球队</span><b>查看它走了多远</b></div>
 </div>
 <div class="bm-chips" role="toolbar" aria-label="按球队高亮晋级路线"><button type="button" class="bm-chip is-all" data-bm-team="" aria-pressed="true">全部</button><?php foreach($teamsInMap as $id=>$t): ?><button type="button" class="bm-chip<?= $id===$championId?' is-champion':'' ?>" data-bm-team="<?= (int)$id ?>" data-reach="<?= (int)$reach[$id] ?>" data-name="<?= e($t->name) ?>" aria-pressed="false" title="<?= e($t->name) ?>"><?= TeamUi::flag($t) ?><span><?= e($t->code) ?></span></button><?php endforeach ?></div>
 <div class="bm-scroll" data-bm-scroll data-lenis-prevent-wheel tabindex="0" aria-label="淘汰赛对阵图，可横向滚动">
  <div class="bm-board" data-bm-board>
   <svg class="bm-lines" data-bm-lines aria-hidden="true"></svg>
   <?php foreach($left as $i=>$ids): ?><?= $column($ids,'left',$i) ?><?php endforeach ?>
   <div class="bm-col bm-center">
    <div class="bm-trophy" aria-hidden="true"><svg viewBox="0 0 64 80"><defs><linearGradient id="bm-gold" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#fff3c4"/><stop offset=".5" stop-color="#f2c75c"/><stop offset="1" stop-color="#a7741c"/></linearGradient></defs><path fill="url(#bm-gold)" d="M14 4h36v14c0 11-7 20-14 22v8c0 4 6 6 10 8v6H18v-6c4-2 10-4 10-8v-8C21 38 14 29 14 18zM6 8h8v6H10c0 7 3 12 6 14l-2 4C9 29 6 22 6 14zm52 0h-8v6h4c0 7-3 12-6 14l2 4c5-3 8-10 8-18zM16 66h32l3 10H13z"/></svg></div>
    <span class="bm-round bm-round-final">决赛<i>FINAL</i></span>
    <?= $card($final,'bm-final') ?>
    <?php if($championId): ?><p class="bm-champion"><span>CHAMPIONS</span><?= TeamUi::label($teamsInMap[$championId]??null) ?></p><?php endif ?>
    <?php if($bronze): ?><span class="bm-round bm-round-bronze">季军赛<i>3RD</i></span><?= $card($bronze,'bm-bronze') ?><?php endif ?>
   </div>
   <?php foreach($right as $i=>$ids): ?><?= $column($ids,'right',$i) ?><?php endforeach ?>
  </div>
 </div>
 <div class="bm-foot"><span><i class="bm-key bm-key-win"></i>胜者 <i class="bm-key bm-key-path"></i>所选球队路线</span><span>连线来自已核验的晋级关系 · 比分为采集快照 · 括号内为点球</span></div>
 <script type="application/json" data-bm-data><?= Json::htmlEncode(['edges'=>array_values(array_filter($links,static fn($e)=>$e['outcome']==='winner')),'champion'=>$championId,'final'=>(int)$final->id]) ?></script>
</section>
