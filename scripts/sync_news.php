<?php
/**
 * 文件作用：命令行同步新闻（旧入口，现在推荐 php yii news/sync）。
 * 作者：梁靖泽_2412852
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Services\NewsSyncService;

$summary = (new NewsSyncService())->sync();
echo json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . PHP_EOL;
