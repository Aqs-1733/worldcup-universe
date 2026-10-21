<?php
/** Error views expose the HTTP outcome, not server paths or database secrets. */
$this->title=(string)$status;
?>
<section class="page-heading error-page"><h1><?= (int)$status ?></h1><p><?= e($message) ?></p><a class="button" href="/">回到首页 ↗</a></section>
