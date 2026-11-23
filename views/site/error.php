<?php
/**
 * 文件作用：视图：错误页，只显示状态码和提示，不暴露服务器路径。
 */
$this->title=(string)$status;
?>
<section class="page-heading error-page"><h1><?= (int)$status ?></h1><p><?= e($message) ?></p><a class="button" href="/">回到首页 ↗</a></section>
