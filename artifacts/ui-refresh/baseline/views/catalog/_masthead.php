<?php
/** Arena-style photographic page cover; the image depicts a venue, not a match report. */
?>
<section class="editorial-cover <?= e($class??'') ?>"><img src="/assets/media/photos/<?= e($photo) ?>.jpg" alt="<?= e($photoLabel) ?>" fetchpriority="high"><div class="cover-copy"><span class="cover-year">2026 世界杯</span><h1><?= e($heading) ?></h1><?php if(!empty($action)): ?><a class="button" href="<?= e($action[0]) ?>"><?= e($action[1]) ?> ↗</a><?php endif ?></div><span class="cover-number" aria-hidden="true"><?= e($number??'26') ?></span></section>
