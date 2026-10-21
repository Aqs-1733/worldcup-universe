<?php
/** Arena-style photographic page cover; the image depicts a venue, not a match report. */
$photoSrc=is_file(ROOT_PATH.'/public/assets/media/photos/display/'.$photo.'.jpg')?'/assets/media/photos/display/'.$photo.'.jpg':'/assets/media/photos/'.$photo.'.jpg'; ?>
<section class="editorial-cover <?= e($class??'') ?>" style="height:clamp(108px,11vw,144px);min-height:0;position:relative;overflow:hidden"><img src="<?= e($photoSrc) ?>" alt="<?= e($photoLabel) ?>" width="1440" height="144" fetchpriority="high"><div class="cover-copy"><h1><?= e($heading) ?></h1><?php if(!empty($action)): ?><a class="button" href="<?= e($action[0]) ?>"><?= e($action[1]) ?> ↗</a><?php endif ?></div></section>
