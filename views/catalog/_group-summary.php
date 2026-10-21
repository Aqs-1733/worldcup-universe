<?php
/** Collapsible group header: one letter tile and four permanent national flags. */
?>
<summary class="group-summary"><span class="group-letter"><?= e($name) ?><small>组</small></span><span class="group-nations"><?php foreach($members as $t): ?><?= \app\services\TeamUi::label($t) ?><?php endforeach ?></span><span class="group-count"><?= e($countLabel) ?></span><span class="group-plus" aria-hidden="true"></span></summary>
