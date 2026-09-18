<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;

$pdo = Database::pdo();

$pdo->exec("UPDATE players SET age = TIMESTAMPDIFF(YEAR, birth_date, '2026-06-11') WHERE birth_date IS NOT NULL AND (age IS NULL OR age = 0)");

$checks = [
    [
        'player_age_derived',
        '球员年龄由出生日期推导',
        '0 missing age when birth_date exists',
        (string) $pdo->query('SELECT COUNT(*) FROM players WHERE birth_date IS NOT NULL AND age IS NULL')->fetchColumn(),
        (int) $pdo->query('SELECT COUNT(*) FROM players WHERE birth_date IS NOT NULL AND age IS NULL')->fetchColumn() === 0,
        '年龄统一按 2026-06-11 世界杯开幕日由 birth_date 推导，避免手填误差。',
    ],
    [
        'country_profile_count',
        '参赛球队国家资料数量',
        '48',
        (string) $pdo->query('SELECT COUNT(*) FROM country_profiles')->fetchColumn(),
        (int) $pdo->query('SELECT COUNT(*) FROM country_profiles')->fetchColumn() === 48,
        '每支参赛球队关联一个国家/足协资料，英格兰和苏格兰按 GB-ENG/GB-SCT 分开。',
    ],
    [
        'country_profile_coords',
        '国家地球定位坐标完整度',
        '0 missing coordinates',
        (string) $pdo->query('SELECT COUNT(*) FROM country_profiles WHERE latitude IS NULL OR longitude IS NULL')->fetchColumn(),
        (int) $pdo->query('SELECT COUNT(*) FROM country_profiles WHERE latitude IS NULL OR longitude IS NULL')->fetchColumn() === 0,
        '地球定位使用国家资料表 latitude/longitude；缺失时不显示定位点。',
    ],
    [
        'backend_framework',
        '后端框架',
        'Yii2',
        'Yii2 2.x + MySQL',
        is_file(ROOT_PATH . '/vendor/yiisoft/yii2/Yii.php'),
        '入口 public/index.php 已改为 Yii2 Application，原业务仓库作为 Yii2 控制器服务层继续复用。',
    ],
];

$stmt = $pdo->prepare(
    'INSERT INTO data_quality_checks (check_key, label, expected_value, actual_value, status, detail, source_url, checked_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
     ON DUPLICATE KEY UPDATE expected_value = VALUES(expected_value), actual_value = VALUES(actual_value), status = VALUES(status), detail = VALUES(detail), source_url = VALUES(source_url), checked_at = NOW()'
);
foreach ($checks as [$key, $label, $expected, $actual, $passed, $detail]) {
    $stmt->execute([$key, $label, $expected, $actual, $passed ? 'pass' : 'warn', $detail, 'https://github.com/Aqs-1733/worldcup-universe']);
}

echo json_encode(['updated_age_rows' => $pdo->query('SELECT COUNT(*) FROM players WHERE age IS NOT NULL')->fetchColumn(), 'checks' => count($checks)], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
