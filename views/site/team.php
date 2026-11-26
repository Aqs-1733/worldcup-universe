<?php
/**
 * 文件作用：视图：团队页，显示成员、课程分工、负责的 MVC 文件和团队/个人作业下载。
 */
$teamName=$team['name'];
$this->title=$teamName.' · 项目团队';
$captain=null;$others=[];
foreach($members as $m){if(!$captain&&$m->role_name==='队长'){$captain=$m;continue;}$others[]=$m;}
if(!$captain&&$others){$captain=array_shift($others);}
$orderedMembers=$captain?[$captain,...$others]:$others;
$duties=$team['duties']??[];
$mvc=$team['mvc']??[];
$teamFiles=$files['team']??[];
$deliverables=array_map(static function(array $d) use($teamFiles){
 $hit=null;foreach($teamFiles as $f){if(mb_stripos($f['title'],$d['match'])!==false){$hit=$f;break;}}
 return $d+['file'=>$hit];
},$team['deliverables']??[]);
$done=count(array_filter($deliverables,static fn($d)=>$d['file']));
$size=static fn(int $b):string=>$b>=1048576?round($b/1048576,1).' MB':max(1,(int)round($b/1024)).' KB';
$tags=$team['tags']??[];
?>
<header class="team-hero">
 <div><h1><?= e($teamName) ?></h1><p><?= e($team['tagline']??'') ?></p></div>
 <nav class="team-anchors" aria-label="本页导航"><a href="#members">成员</a><a href="#duties">分工</a><a href="#downloads">作业下载</a></nav>
</header>

<section id="members" class="team-members" aria-label="团队成员">
 <div class="team-grid">
  <?php foreach($orderedMembers as $m):
   $school=trim(explode('·',(string)$m->bio,2)[0]);
   $details=implode(' · ',array_filter([$school,(string)$m->student_no],static fn($part)=>$part!==''));
   $avatar=safe_url($m->photo_url);
   $chips=$tags[$m->student_no]??$duties[$m->student_no]??[];
   $lead=$m===$captain;
  ?>
   <article class="team-card<?= $lead?' is-captain':'' ?>">
    <span class="team-avatar" aria-hidden="true"><?php if($avatar!=='#'): ?><img src="<?= e($avatar) ?>" alt="" referrerpolicy="no-referrer"><?php else: ?><?= e(mb_substr($m->name,0,1)) ?><?php endif ?></span>
    <div class="team-card-body">
     <h3><?= e($m->name) ?><?php if($lead): ?><span class="team-role">队长</span><?php endif ?></h3>
     <?php if($details!==''): ?><p><?= e($details) ?></p><?php endif ?>
     <?php if($chips): ?><ul class="team-duty-chips"><?php foreach($chips as $chip): ?><li><?= e($chip) ?></li><?php endforeach ?></ul><?php endif ?>
     <div class="team-introduction">
      <h4>自我介绍</h4>
      <p><?= e($m->self_introduction ?? '暂未填写') ?></p>
     </div>
    </div>
   </article>
  <?php endforeach ?>
 </div>
</section>

<section id="duties" class="team-duties">
 <div class="team-section-head"><h2>课程分工</h2></div>
 <div class="team-duties-body">
  <table class="team-matrix">
   <thead><tr><th scope="col">成员</th><th scope="col">负责内容</th><th scope="col">负责的 MVC 文件</th></tr></thead>
   <tbody><?php foreach($orderedMembers as $m): ?><tr><th scope="row"><?= e($m->name) ?><?php if($m->role_name==='队长'): ?><span class="team-role">队长</span><?php endif ?></th><td><?php $list=$duties[$m->student_no]??[]; ?><?= $list?e(implode('、',$list)):'<span class="team-muted">待补充</span>' ?></td><td class="team-mvc"><?php foreach($mvc[$m->student_no]??[] as $layer=>$path): ?><div><b><?= e($layer) ?></b><code><?= e($path) ?></code></div><?php endforeach ?></td></tr><?php endforeach ?></tbody>
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
   <?php if($scope==='personal'): ?><?php
    // 个人作业按成员分组：文件标题或文件名里的学号决定归属，找不到学号的放到最后
    $byStudent=[];foreach($files['personal']??[] as $f){$byStudent[$f['student']][]=$f;}
    $groups=[];foreach($orderedMembers as $m){$groups[]=['name'=>$m->name.' · '.$m->student_no,'files'=>$byStudent[$m->student_no]??[]];unset($byStudent[$m->student_no]);}
    $rest=array_merge(...array_values($byStudent));if($rest){$groups[]=['name'=>'未标注学号','files'=>$rest];}
   ?><?php foreach($groups as $g): ?><div class="team-person"><h4><?= e($g['name']) ?><span><?= count($g['files']) ?></span></h4>
    <?php if($g['files']): ?><ul><?php foreach($g['files'] as $f): ?><li><a href="<?= e($f['url']) ?>" download><span class="team-ext"><?= e($f['ext']) ?></span><span class="team-file"><b><?= e($f['title']) ?></b><small><?= e($size($f['size'])) ?> · <?= e(date('Y.m.d',$f['updated'])) ?></small></span><span class="team-dl" aria-hidden="true">↓</span></a></li><?php endforeach ?></ul><?php else: ?><p class="team-empty">暂未上传</p><?php endif ?></div><?php endforeach ?>
   <?php elseif(!empty($files[$scope])): ?><ul><?php foreach($files[$scope] as $f): ?><li><a href="<?= e($f['url']) ?>" download><span class="team-ext"><?= e($f['ext']) ?></span><span class="team-file"><b><?= e($f['title']) ?></b><small><?= e($size($f['size'])) ?> · <?= e(date('Y.m.d',$f['updated'])) ?></small></span><span class="team-dl" aria-hidden="true">↓</span></a></li><?php endforeach ?></ul>
   <?php else: ?><p class="team-empty">暂无文件</p><?php endif ?>
  </section><?php endforeach ?>
 </div>
</section>
