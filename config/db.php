<?php

declare(strict_types=1);

$database = (string) env('DB_DATABASE', 'worldcup_universe');
$host = (string) env('DB_HOST', '127.0.0.1');
$port = (string) env('DB_PORT', '3306');
$charset = (string) env('DB_CHARSET', 'utf8mb4');

return [
    'class' => yii\db\Connection::class,
    'dsn' => "mysql:host={$host};port={$port};dbname={$database};charset={$charset}",
    'username' => (string) env('DB_USERNAME', 'root'),
    'password' => (string) env('DB_PASSWORD', ''),
    'charset' => $charset,
    'enableSchemaCache' => (bool) env('DB_SCHEMA_CACHE', false),
];
