<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Services\NewsSyncService;

$limit = isset($argv[1]) ? max(1, (int) $argv[1]) : 80;
$summary = (new NewsSyncService())->translatePending($limit);

echo json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . PHP_EOL;
