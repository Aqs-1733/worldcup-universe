<?php
/** Team homepage: members (captain first), course division of labour and coursework downloads. */
$this->title='项目团队';
$captain=null;$others=[];
foreach($members as $m){if(!$captain&&$m->role_name==='队长'){$captain=$m;continue;}$others[]=$m;}
if(!$captain&&$others){$captain=array_shift($others);}
$duties=$team['duties']??[];
$teamFiles=$files['team']??[];
$deliverables=array_map(static function(array $d) use($teamFiles){
 $hit=null;foreach($teamFiles as $f){if(mb_stripos($f['title'],$d['match'])!==false){$hit=$f;break;}}
 return $d+['file'=>$hit];
},$team['deliverables']??[]);
$done=count(array_filter($deliverables,static fn($d)=>$d['file']));
$size=static fn(int $b):string=>$b>=1048576?round($b/1048576,1).' MB':max(1,(int)round($b/1024)).' KB';
$card=static function($m,bool $lead=false) use($duties): string{
 $school=trim(explode('·',(string)$m->bio)[0]);
 $avatar=$m->photo_url?'<img src="'.e(safe_url($m->photo_url)).'" alt="">':e(mb_substr($m->name,0,1));
 $chips='';foreach($duties[$m->student_no]??[] as $d)$chips.='<li>'.e($d).'</li>';
 return '<article class="team-card'.($lead?' is-captain':'').'"><span class="team-avatar">'.$avatar.'</span><div class="team-card-body">'
  .($lead?'<span class="team-badge">队长</span>':'').'<h3>'.e($m->name).'</h3><p>'.e($school).' · '.e($m->student_no).'</p>'
  .($chips?'<ul class="team-duty-chips">'.$chips.'</ul>':'').'</div></article>';
};
?>
<header class="team-hero">
 <div><h1><?= e($team['project']??'项目团队') ?> · 项目团队</h1><p><?= e($team['tagline']??'') ?></p></div>
 <nav class="team-anchors" aria-label="本页导航"><a href="#members">成员</a><a href="#duties">分工</a><a href="#downloads">作业下载</a></nav>
</header>

<section id="members" class="team-members" aria-label="团队成员">
 <?php if($captain): ?><?= $card($captain,true) ?><?php endif ?>
 <div class="team-grid"><?php foreach($others as $m): ?><?= $card($m) ?><?php endforeach ?></div>
</section>

<section id="duties" class="team-duties">
 <div class="team-section-head"><h2>课程分工</h2></div>
 <div class="team-duties-body">
  <table class="team-matrix">
   <thead><tr><th scope="col">成员</th><th scope="col">负责内容</th></tr></thead>
   <tbody><?php foreach(array_merge($captain?[$captain]:[],$others) as $m): ?><tr><th scope="row"><?= e($m->name) ?><?php if($m->role_name==='队长'): ?><span class="team-badge">队长</span><?php endif ?></th><td><?php $list=$duties[$m->student_no]??[]; ?><?= $list?e(implode('、',$list)):'<span class="team-muted">待补充</span>' ?></td></tr><?php endforeach ?></tbody>
  </table>
  <aside class="team-progress" aria-label="团队交付进度">
   <div class="team-progress-head"><b><?= $done ?><i>/<?= count($deliverables) ?></i></b><span>截止 <?= e(str_replace('-','.',$team['deadline']??'')) ?></span></div>
   <div class="team-progress-bar"><i style="width:<?= count($deliverables)?round($done/count($deliverables)*100):0 ?>%"></i></div>
   <ol><?php foreach($deliverables as $d): ?><li class="<?= $d['file']?'is-done':'' ?>"><span><?= e($d['title']) ?></span><?php if($d['file']): ?><a href="<?= e($d['file']['url']) ?>">下载 ↓</a><?php else: ?><em>待提交</em><?php endif ?></li><?php endforeach ?></ol>
  </aside>
 </div>
</section>

<section id="downloads" class="team-downloads">
 <div class="team-section-head"><h2>作业下载</h2></div>
 <div class="team-download-grid">
  <?php foreach(app\services\CourseworkLibrary::SCOPES as $scope=>$label): ?><section class="team-download-panel" aria-labelledby="dl-<?= e($scope) ?>">
   <h3 id="dl-<?= e($scope) ?>"><?= e($label) ?><span><?= count($files[$scope]??[]) ?></span></h3>
   <?php if(!empty($files[$scope])): ?><ul><?php foreach($files[$scope] as $f): ?><li><a href="<?= e($f['url']) ?>" download><span class="team-ext"><?= e($f['ext']) ?></span><span class="team-file"><b><?= e($f['title']) ?></b><small><?= e($size($f['size'])) ?> · <?= e(date('Y.m.d',$f['updated'])) ?></small></span><span class="team-dl" aria-hidden="true">↓</span></a></li><?php endforeach ?></ul>
   <?php else: ?><p class="team-empty">暂无文件</p><?php endif ?>
  </section><?php endforeach ?>
 </div>
</section>
