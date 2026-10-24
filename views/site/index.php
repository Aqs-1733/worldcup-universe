<?php
/** Motion-led opening: tunnel → stadium → champion. Real results remain in the sections below. */
use app\services\{ImmersiveService,TeamUi};
$this->title='世界杯宇宙';
$presentation=require ROOT_PATH.'/config/presentation.php';$cover=$presentation['home'];$cine=$presentation['cinematic'];
$immersive=new ImmersiveService();
$final=$immersive->finalMatch();$winner=$final?ImmersiveService::winnerSide($final):null;
$champion=$winner?($winner==='home'?$final->homeTeam:$final->awayTeam):null;$runnerUp=$winner?($winner==='home'?$final->awayTeam:$final->homeTeam):null;
$ticker=$immersive->tickerMatches();$rail=$immersive->showcasePlayers(12);
$count=static fn(string $class):int=>(int)$class::find()->where(['not',['official_import_id'=>null]])->count();
$span=app\models\MatchGame::find()->where(['not',['official_import_id'=>null]])->select(['MIN(starts_at) AS first','MAX(starts_at) AS last'])->asArray()->one();
$days=$span&&$span['first']?(int)((strtotime(substr($span['last'],0,10))-strtotime(substr($span['first'],0,10)))/86400)+1:null;
$stats=[[$count(app\models\Team::class),'支球队','NATIONS'],[count(require ROOT_PATH.'/config/venue-geo.php'),'座城市','HOST CITIES'],[$count(app\models\MatchGame::class),'场比赛','MATCHES'],[$days,'天','DAYS']];
$heroVideo=!empty($cine['video'])&&app\services\LocalVideoLibrary::safeFile(basename($cine['video']))?'/stream/'.rawurlencode(basename($cine['video'])):null;
$position=['GK'=>'守门员','DF'=>'后卫','MF'=>'中场','FW'=>'前锋'];
?>
<div class="cine-preloader" data-cine-preloader aria-hidden="true"><div class="cine-preloader-inner"><span class="cine-preloader-mark">W</span><span class="cine-preloader-title">WORLD CUP UNIVERSE</span><span class="cine-preloader-count" data-cine-count>00</span></div><span class="cine-preloader-bar" data-cine-bar></span></div>
<section class="cine-hero" data-cine-hero aria-labelledby="hero-title" style="--cine-length:<?= (int)$cine['scrollLength'] ?>">
 <div class="cine-stage" data-cine-stage>
  <div class="cine-layer cine-tunnel" data-cine-tunnel><img src="<?= e($cover['image']) ?>" alt="<?= e($cover['imageAlt']) ?>" width="1408" height="768" fetchpriority="high"></div>
  <div class="cine-layer cine-stadium" data-cine-stadium><?php if($heroVideo): ?><video src="<?= e($heroVideo) ?>" poster="<?= e($cine['stadiumImage']) ?>" muted loop playsinline preload="metadata" data-cine-video></video><?php else: ?><img src="<?= e($cine['stadiumImage']) ?>" alt="<?= e($cine['stadiumAlt']) ?>" loading="lazy"><?php endif ?></div>
  <div class="cine-layer cine-final-bg" data-cine-final-bg><img src="<?= e($cine['finalImage']) ?>" alt="" loading="lazy"></div>
  <div class="cine-flash" data-cine-flash aria-hidden="true"></div>
  <div class="cine-shade" aria-hidden="true"></div>
  <div class="cine-grain" aria-hidden="true"></div>

  <div class="cine-scene cine-scene-tunnel" data-cine-scene="1">
   <p class="cine-eyebrow" data-cine-eyebrow><span></span><?= e($cine['eyebrow']) ?></p>
   <h1 id="hero-title" class="cine-title" data-split><?php foreach($cine['titleLines'] as $line): ?><span class="cine-line"><?= e($line) ?></span><?php endforeach ?></h1>
   <p class="cine-subtitle" data-cine-fade><?= e($cine['subtitle']) ?></p>
   <div class="cine-actions" data-cine-fade><a class="cine-btn cine-btn-solid" href="<?= e($cover['primary']['url']) ?>" data-magnetic><?= e($cover['primary']['label']) ?><span aria-hidden="true">↗</span></a><a class="cine-btn" href="<?= e($cover['secondary']['url']) ?>" data-magnetic><?= e($cover['secondary']['label']) ?><span aria-hidden="true">→</span></a></div>
  </div>

  <div class="cine-scene cine-scene-stadium" data-cine-scene="2">
   <p class="cine-kicker"><?= e($cine['stadiumKicker']) ?></p>
   <div class="cine-year" aria-hidden="true">2026</div>
   <h2 class="cine-stadium-title"><?= e($cine['stadiumTitle']) ?></h2>
   <dl class="cine-stats"><?php foreach($stats as [$value,$label,$en]): ?><?php if($value): ?><div class="cine-stat"><dt><?= e($en) ?></dt><dd><b data-count-to="<?= (int)$value ?>"><?= (int)$value ?></b><span><?= e($label) ?></span></dd></div><?php endif ?><?php endforeach ?></dl>
  </div>

  <?php if($final&&$champion): ?><div class="cine-scene cine-scene-final" data-cine-scene="3">
   <p class="cine-kicker cine-gold">FIFA WORLD CUP 2026™ · CHAMPIONS</p>
   <div class="cine-champion">
    <img class="cine-champion-flag" src="<?= e(TeamUi::url($champion)) ?>" alt="<?= e($champion->name) ?>国旗">
    <h2><?= e($champion->name) ?><small><?= e(strtoupper($champion->name_original)) ?></small></h2>
   </div>
   <div class="cine-final-score"><span><?= TeamUi::label($final->homeTeam) ?></span><b><?= (int)$final->home_score ?><i>:</i><?= (int)$final->away_score ?></b><span><?= TeamUi::label($final->awayTeam) ?></span></div>
   <p class="cine-final-meta">决赛 · <?= e(date('Y.m.d',strtotime($final->starts_at))) ?> · <?= e($final->venue?->name_cn?:($final->venue?->name_original?:'')) ?></p>
   <div class="cine-actions"><a class="cine-btn cine-btn-gold" href="/watch?clip=final-highlights#cinema" data-magnetic>观看决赛集锦<span aria-hidden="true">▶</span></a><a class="cine-btn" href="/matches/<?= (int)$final->id ?>" data-magnetic>决赛档案<span aria-hidden="true">↗</span></a><a class="cine-btn" href="/worldcup#bracket-map" data-magnetic>回看淘汰赛之路<span aria-hidden="true">→</span></a></div>
  </div><?php endif ?>

  <ol class="cine-chapters" aria-hidden="true"><li data-cine-chapter="1"><span>01</span>通道</li><li data-cine-chapter="2"><span>02</span>球场</li><li data-cine-chapter="3"><span>03</span>冠军</li><i class="cine-chapter-bar"><i data-cine-progress></i></i></ol>
  <a class="cine-scroll-cue" href="#matchday" data-hero-explore><span class="cine-cue-line" aria-hidden="true"></span>向下滚动 · 走进球场</a>
  <a class="cine-credit" href="<?= e($cover['sourceUrl']) ?>" target="_blank" rel="noopener noreferrer"><?= e($cover['sourceLabel']) ?> ↗</a>
 </div>
</section>

<?php if($ticker): ?><section class="cine-ticker" aria-label="最近比分" data-ticker><div class="cine-ticker-track" data-ticker-track><?php for($loop=0;$loop<2;$loop++): ?><ul <?= $loop?'aria-hidden="true"':'' ?>><?php foreach($ticker as $m): $w=ImmersiveService::winnerSide($m); ?><li><a href="/matches/<?= (int)$m->id ?>" <?= $loop?'tabindex="-1"':'' ?>><em><?= e($m->stage) ?></em><span class="<?= $w==='home'?'is-win':'' ?>"><?= TeamUi::flag($m->homeTeam) ?><?= e($m->homeTeam?->code) ?></span><b><?= (int)$m->home_score ?> – <?= (int)$m->away_score ?></b><span class="<?= $w==='away'?'is-win':'' ?>"><?= e($m->awayTeam?->code) ?><?= TeamUi::flag($m->awayTeam) ?></span></a></li><?php endforeach ?></ul><?php endfor ?></div></section><?php endif ?>

<section class="section fixtures-section" id="matchday" data-reveal-group><div class="section-head"><div><span class="edition-kicker" data-reveal>MATCHDAY ARCHIVE</span><h2 data-reveal-title>球场上的每一刻</h2></div><a href="/worldcup" data-reveal>查看全部赛程 →</a></div><div class="edition-results" data-reveal-children><?php foreach($matches as $m): ?><?= $this->render('/catalog/_match',['model'=>$m]) ?><?php endforeach ?></div></section>

<?php if($rail): ?><section class="player-rail" data-player-rail aria-labelledby="rail-title">
 <div class="player-rail-sticky">
  <header class="player-rail-head"><div><span class="edition-kicker">THE PEOPLE OF THE GAME</span><h2 id="rail-title" data-reveal-title>场上的主角</h2></div><p>横向滑动，认识 <?= count($rail) ?> 位代表各自国家的球星。<a href="/players">全部 1,248 位球员 →</a></p></header>
  <div class="player-rail-viewport" data-rail-viewport tabindex="0" aria-label="球员卡片，可横向滚动"><div class="player-rail-track" data-rail-track>
   <?php foreach($rail as $i=>$p): ?><a class="rail-card" href="/players/<?= e($p->slug) ?>" data-tilt style="--card-index:<?= $i ?>">
    <span class="rail-card-number" aria-hidden="true"><?= (int)$p->shirt_number ?></span>
    <span class="rail-card-code" aria-hidden="true"><?= e($p->team?->code) ?></span>
    <img class="rail-card-photo" src="<?= e($p->displayPhotoUrl) ?>" alt="<?= e($p->name_original) ?> · FIFA 官方照片" decoding="async">
    <span class="rail-card-body"><span class="rail-card-team"><?= TeamUi::flag($p->team) ?><?= e($p->team?->name) ?> · <?= e($position[$p->position]??$p->position) ?></span><b><?= e($p->name) ?></b><span class="rail-card-stats"><span><i><?= e($p->caps??'—') ?></i>出场</span><span><i><?= e($p->goals??'—') ?></i>进球</span></span></span>
    <span class="rail-card-arrow" aria-hidden="true">↗</span>
   </a><?php endforeach ?>
   <a class="rail-card rail-card-more" href="/players"><span>查看全部球员</span><b>1,248</b><span aria-hidden="true">→</span></a>
  </div></div>
  <div class="player-rail-progress" aria-hidden="true"><i data-rail-progress></i></div>
 </div>
</section><?php endif ?>

<section class="section teams-feature" data-reveal-group><div class="feature-intro"><div><span class="edition-kicker" data-reveal>NATIONS OF THE WORLD</span><h2 data-reveal-title>不同的球衣，同样的热爱。</h2></div><a class="edition-inline-link" href="/teams" data-reveal>全部球队 ↗</a></div><div class="nations-grid edition-team-grid cine-team-grid" data-reveal-children><?php foreach($teams as $t): ?><?= $this->render('/catalog/_team',['model'=>$t]) ?><?php endforeach ?></div></section>
<?php $shelf=(new app\services\LocalVideoLibrary())->all();$lead=null;foreach($shelf as $s){if($s['provider']==='local'){$lead=$s;break;}}$lead??=$shelf[0]??null;$badge=['local'=>'本地视频','bilibili'=>'B站','youtube'=>'YouTube','missing'=>'待添加']; ?>
<?php if($lead): ?><section class="home-cinema" aria-labelledby="home-cinema-title">
 <a class="home-cinema-lead" href="/watch?clip=<?= e($lead['slug']) ?>#cinema" data-tilt>
  <?php if($lead['provider']==='local'): ?><video src="<?= e($lead['src']) ?>" poster="<?= e($lead['poster']) ?>" muted loop playsinline preload="none" data-hover-play></video><?php else: ?><img src="<?= e($lead['poster']) ?>" alt="" loading="lazy"><?php endif ?>
  <span class="home-cinema-play" aria-hidden="true"><i></i></span>
  <span class="home-cinema-copy"><span class="cp-tag"><?= e($badge[$lead['provider']]) ?></span><b><?= e($lead['title']) ?></b><small><?= e($lead['subtitle']) ?></small></span>
 </a>
 <div class="home-cinema-side">
  <span class="edition-kicker">REPLAY · 影像馆</span><h2 id="home-cinema-title" data-reveal-title>再看一遍，那些瞬间。</h2>
  <p>集锦和回放都在「回放」频道。把视频文件放进 <code>public/assets/media/videos/</code> 就能在站内直接播放。</p>
  <ul><?php foreach(array_slice($shelf,0,4) as $item): ?><li><a href="/watch?clip=<?= e($item['slug']) ?>#cinema"><span><?= e($item['title']) ?></span><em><?= e($badge[$item['provider']]) ?></em></a></li><?php endforeach ?></ul>
  <a class="cine-btn cine-btn-solid" href="/watch#cinema" data-magnetic>进入影像馆<span aria-hidden="true">▶</span></a>
 </div>
</section><?php endif ?>
<section class="community-feature" data-parallax-bg><h2 data-reveal-title>终场哨后，讨论继续。</h2><p data-reveal>选择主队，发布话题，和球迷聊聊比赛。</p><a class="button" href="/forum" data-reveal data-magnetic>进入球迷论坛 ↗</a></section>
<section class="section" data-reveal-group><div class="section-head"><h2 data-reveal-title>世界杯新闻</h2><a href="/news" data-reveal>全部新闻 →</a></div><div class="editorial-news home-news" data-reveal-children><?php foreach($news as $n): ?><?= $this->render('/catalog/_news-card',['model'=>$n]) ?><?php endforeach ?></div></section>
<section id="our-team" class="section" data-reveal-group><div class="section-head"><h2 data-reveal-title>项目团队</h2><a href="/deliverables" data-reveal>课程作业下载 →</a></div><div class="member-grid" data-reveal-children><?php foreach($members as $member): ?><article class="member-block"><span class="member-initial"><?= e(mb_substr($member->name,0,1)) ?></span><small><?= e($member->role_name) ?></small><h3><?= e(preg_match('/^成员[1-4]$/u',$member->name)?'团队资料待完善':$member->name) ?></h3><p><?= e($member->bio) ?></p></article><?php endforeach ?></div></section>
