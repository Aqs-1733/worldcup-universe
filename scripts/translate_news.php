<?php
/**
 * 文件作用：调用翻译服务把新闻标题、摘要翻译成中文。
 * 作者：梁靖泽_2412852
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Services\NewsSyncService;

$limit = isset($argv[1]) ? max(1, (int) $argv[1]) : 80;
$summary = (new NewsSyncService())->translatePending($limit);

echo json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . PHP_EOL;
