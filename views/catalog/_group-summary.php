<?php
/**
 * 文件作用：局部视图：可折叠的小组标题（组名+四支球队国旗）。
 * 作者：梁靖泽_2412852
 */
?>
<summary class="group-summary"><span class="group-letter"><?= e($name) ?><small>组</small></span><span class="group-nations"><?php foreach($members as $t): ?><?= \app\services\TeamUi::label($t) ?><?php endforeach ?></span><span class="group-count"><?= e($countLabel) ?></span><span class="group-plus" aria-hidden="true"></span></summary>
