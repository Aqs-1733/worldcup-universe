<?php
/** Team-only covers: verified 2026 squad photo or an explicit flag design, never a player portrait. */
$photo=app\services\TeamPhoto::get($model->code);
?>
<a href="/teams/<?= e($model->slug) ?>" class="nation-card squad-card <?= $photo?'has-squad-photo':'flag-cover' ?>"><div class="squad-cover"><?php if($photo): ?><img class="squad-photo" src="<?= e($photo['path']) ?>" alt="<?= e($model->name) ?> · 2026 世界杯球队合照" loading="lazy"><?php else: ?><span class="flag-cover-code" aria-hidden="true"><?= e($model->code) ?></span><div class="flag-cover-emblem"><?= app\services\TeamUi::flag($model) ?></div><?php endif ?><span class="squad-group">小组 <?= e($model->group_name) ?></span></div><div class="nation-bottom"><h3><?= app\services\TeamUi::label($model) ?><span>↗</span></h3><span class="meta">26 人阵容 · <?= e(['UEFA'=>'欧洲','CONMEBOL'=>'南美洲','CONCACAF'=>'中北美洲','CAF'=>'非洲','AFC'=>'亚洲','OFC'=>'大洋洲'][$model->confederation]??'国家队') ?></span></div></a>
