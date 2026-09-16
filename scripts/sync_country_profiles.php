<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;
use App\Services\HttpClient;

$pdo = Database::pdo();
$http = new HttpClient();
$codes = $pdo->query('SELECT country_code, GROUP_CONCAT(DISTINCT name_cn ORDER BY name_cn SEPARATOR "、") AS team_names, COUNT(*) AS team_count FROM teams WHERE country_code IS NOT NULL AND country_code <> "" GROUP BY country_code ORDER BY country_code')->fetchAll();
$onlyCodes = [];
foreach (array_slice($argv ?? [], 1) as $arg) {
    if (str_starts_with((string) $arg, '--codes=')) {
        $onlyCodes = array_filter(array_map(static fn (string $code): string => strtoupper(trim($code)), explode(',', substr((string) $arg, 8))));
    }
}
if ($onlyCodes) {
    $codes = array_values(array_filter($codes, static fn (array $row): bool => in_array(strtoupper((string) $row['country_code']), $onlyCodes, true)));
}

$sourceUrl = 'https://raw.githubusercontent.com/mledoze/countries/master/countries.json';
$payload = $http->get($sourceUrl, ['Accept' => 'application/json'], 30);
$allCountries = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
$byCode = [];
foreach ($allCountries as $country) {
    if (is_array($country) && !empty($country['cca2'])) {
        $byCode[strtoupper((string) $country['cca2'])] = $country;
    }
}

$stmt = $pdo->prepare(
    'INSERT INTO country_profiles (country_code, country_name_cn, country_name_original, capital, region, subregion, languages, currencies, population, area_km2, map_url, fifa_team_count, travel_summary, culture_summary, source_url, source_synced_at)
     VALUES (:country_code, :country_name_cn, :country_name_original, :capital, :region, :subregion, :languages, :currencies, :population, :area_km2, :map_url, :fifa_team_count, :travel_summary, :culture_summary, :source_url, NOW())
     ON DUPLICATE KEY UPDATE country_name_cn = VALUES(country_name_cn), country_name_original = VALUES(country_name_original), capital = VALUES(capital), region = VALUES(region), subregion = VALUES(subregion), languages = VALUES(languages), currencies = VALUES(currencies), population = VALUES(population), area_km2 = VALUES(area_km2), map_url = VALUES(map_url), fifa_team_count = VALUES(fifa_team_count), travel_summary = VALUES(travel_summary), culture_summary = VALUES(culture_summary), source_url = VALUES(source_url), source_synced_at = NOW()'
);

$ok = 0;
$failed = [];
foreach ($codes as $row) {
    $code = strtoupper((string) $row['country_code']);
    try {
        $json = $byCode[$code] ?? null;
        if (!$json) {
            throw new RuntimeException('country code not found in countries dataset');
        }
        $nameOriginal = (string) ($json['name']['common'] ?? $row['team_names'] ?? $code);
        $nameCn = (string) ($json['translations']['zho']['common'] ?? $row['team_names'] ?? $nameOriginal);
        $capital = implode('、', array_filter(array_map('strval', (array) ($json['capital'] ?? []))));
        $languages = implode('、', array_values(array_map('strval', (array) ($json['languages'] ?? []))));
        $currencyNames = [];
        foreach ((array) ($json['currencies'] ?? []) as $currency) {
            if (is_array($currency) && !empty($currency['name'])) {
                $currencyNames[] = (string) $currency['name'];
            }
        }
        $currencies = implode('、', array_unique($currencyNames));
        $region = (string) ($json['region'] ?? '');
        $subregion = (string) ($json['subregion'] ?? '');
        $mapUrl = (string) ($json['maps']['googleMaps'] ?? $json['maps']['openStreetMaps'] ?? '');
        $travel = travelSummary($code, $nameCn, $capital, $region, $languages, $currencies, $mapUrl);
        $culture = cultureSummary($nameCn, $region, $subregion, $languages, $currencies, (string) $row['team_names']);
        $stmt->execute([
            'country_code' => $code,
            'country_name_cn' => $nameCn,
            'country_name_original' => $nameOriginal,
            'capital' => $capital ?: null,
            'region' => $region ?: null,
            'subregion' => $subregion ?: null,
            'languages' => $languages ?: null,
            'currencies' => $currencies ?: null,
            'population' => isset($json['population']) ? (int) $json['population'] : null,
            'area_km2' => isset($json['area']) ? (float) $json['area'] : null,
            'map_url' => $mapUrl ?: null,
            'fifa_team_count' => (int) $row['team_count'],
            'travel_summary' => $travel,
            'culture_summary' => $culture,
            'source_url' => $sourceUrl,
        ]);
        $ok++;
    } catch (Throwable $error) {
        $failed[] = $code . ': ' . $error->getMessage();
    }
}

echo json_encode(['synced' => $ok, 'failed' => count($failed), 'errors' => $failed], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . PHP_EOL;

function travelSummary(string $code, string $name, string $capital, string $region, string $languages, string $currencies, string $mapUrl): string
{
    $special = [
        'US' => '美国是 2026 世界杯主办国之一，适合把比赛城市、国家公园、博物馆和城市体育文化串成观赛旅行路线。',
        'CA' => '加拿大是 2026 世界杯主办国之一，温哥华和多伦多适合结合城市观赛、海湾景观、多元社区和冬夏户外活动。',
        'MX' => '墨西哥是 2026 世界杯主办国之一，墨西哥城、瓜达拉哈拉、蒙特雷适合结合球场、历史街区、美食和城市文化体验。',
    ];
    if (isset($special[$code])) {
        return $special[$code];
    }

    $parts = [];
    if ($capital !== '') {
        $parts[] = "首都/行政中心可关注 {$capital}";
    }
    if ($region !== '') {
        $parts[] = "区域位于 {$region}";
    }
    if ($languages !== '') {
        $parts[] = "常用语言包括 {$languages}";
    }
    if ($currencies !== '') {
        $parts[] = "旅行前可准备 {$currencies} 相关支付信息";
    }
    $text = $parts ? implode('，', $parts) . '。' : "{$name} 的旅行资料待继续补充。";
    if ($mapUrl !== '') {
        $text .= '页面提供地图入口，便于继续规划路线。';
    }
    return $text;
}

function cultureSummary(string $name, string $region, string $subregion, string $languages, string $currencies, string $teamNames): string
{
    $place = trim($region . ($subregion !== '' ? ' / ' . $subregion : ''));
    $bits = [];
    if ($teamNames !== '') {
        $bits[] = "世界杯关联球队：{$teamNames}";
    }
    if ($place !== '') {
        $bits[] = "地理文化区：{$place}";
    }
    if ($languages !== '') {
        $bits[] = "语言：{$languages}";
    }
    if ($currencies !== '') {
        $bits[] = "货币：{$currencies}";
    }
    return $bits ? implode('；', $bits) . '。' : "{$name} 的文化信息待同步。";
}
