<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;

$host = (string) env('DB_HOST', '127.0.0.1');
$port = (string) env('DB_PORT', '3306');
$charset = (string) env('DB_CHARSET', 'utf8mb4');
$username = (string) env('DB_USERNAME', 'root');
$password = (string) env('DB_PASSWORD', '');

$server = new PDO("mysql:host={$host};port={$port};charset={$charset}", $username, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$schema = file_get_contents(ROOT_PATH . '/database/schema.sql');
foreach (array_filter(array_map('trim', explode(';', (string) $schema))) as $statement) {
    $server->exec($statement);
}

$pdo = Database::pdo();

$pdo->exec("INSERT IGNORE INTO roles (name, label) VALUES ('admin', '管理员'), ('user', '普通用户')");

$tags = [
    ['match', '比赛内容', '#16a34a'],
    ['tactics', '战术分析', '#2563eb'],
    ['team', '球队动态', '#f59e0b'],
    ['fan', '球迷内容', '#ec4899'],
    ['entertainment', '娱乐内容', '#ef4444'],
    ['fanmade', '二创内容', '#8b5cf6'],
    ['worldcup', '世界杯', '#22c55e'],
    ['football', '足球', '#06b6d4'],
    ['technology', '技术', '#64748b'],
];
$tagStmt = $pdo->prepare('INSERT IGNORE INTO tags (slug, name_cn, color) VALUES (?, ?, ?)');
foreach ($tags as $tag) {
    $tagStmt->execute($tag);
}

$sources = [
    ['fifa_teams', 'FIFA Teams', (string) env('FIFA_TEAMS_URL'), 'official', 100],
    ['fifa_fixtures', 'FIFA Scores & Fixtures', (string) env('FIFA_FIXTURES_URL'), 'official', 100],
    ['fifa_standings', 'FIFA Standings', (string) env('FIFA_STANDINGS_URL'), 'official', 100],
    ['espn_scoreboard', 'ESPN FIFA World Cup Scoreboard', (string) env('ESPN_SCOREBOARD_URL'), 'api', 88],
    ['espn_standings', 'ESPN FIFA World Cup Standings', (string) env('ESPN_STANDINGS_URL'), 'api', 88],
    ['wikipedia_squads', 'Wikipedia 2026 FIFA World Cup squads', (string) env('WIKIPEDIA_SQUADS_URL'), 'wiki', 70],
];
$sourceStmt = $pdo->prepare('INSERT INTO data_sources (source_key, name, url, source_type, trust_level) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE url = VALUES(url), trust_level = VALUES(trust_level)');
foreach ($sources as $source) {
    $sourceStmt->execute($source);
}

$newsSources = [
    ['BBC Sport Football', 'https://www.bbc.com/sport/football', 'https://feeds.bbci.co.uk/sport/football/rss.xml', 'en', 86],
    ['ESPN Soccer', 'https://www.espn.com/soccer/', 'https://www.espn.com/espn/rss/soccer/news', 'en', 84],
    ['The Guardian Football', 'https://www.theguardian.com/football', 'https://www.theguardian.com/football/rss', 'en', 82],
    ['CBS Sports Soccer', 'https://www.cbssports.com/soccer/', 'https://www.cbssports.com/rss/headlines/soccer/', 'en', 80],
    ['The New York Times Soccer', 'https://www.nytimes.com/section/sports/soccer', 'https://rss.nytimes.com/services/xml/rss/nyt/Soccer.xml', 'en', 79],
    ['Sky Sports Football', 'https://www.skysports.com/football', 'https://www.skysports.com/rss/12040', 'en', 78],
];
$newsStmt = $pdo->prepare('INSERT INTO news_sources (name, url, rss_url, language_code, trust_level) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE rss_url = VALUES(rss_url), trust_level = VALUES(trust_level)');
foreach ($newsSources as $source) {
    $newsStmt->execute($source);
}

$memberStmt = $pdo->prepare('INSERT INTO team_members (name, student_no, role_name, bio, sort_order) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE role_name = VALUES(role_name)');
$seedMembers = [
    ['成员1', '', '项目负责人', '负责需求整理、答辩组织和整体验收。', 1],
    ['成员2', '', '前端与交互', '负责世界杯页面、动效和用户体验。', 2],
    ['成员3', '', '后端与数据库', '负责 PHP、MySQL、登录注册和后台管理。', 3],
    ['成员4', '', '数据与文档', '负责真实数据来源、新闻同步和需求文档。', 4],
];
foreach ($seedMembers as $member) {
    $memberStmt->execute($member);
}

$artifactStmt = $pdo->prepare('INSERT INTO coursework_artifacts (artifact_key, stage, title, requirement_summary, evidence_path, status, sort_order, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE stage = VALUES(stage), title = VALUES(title), requirement_summary = VALUES(requirement_summary), evidence_path = VALUES(evidence_path), status = VALUES(status), sort_order = VALUES(sort_order), notes = VALUES(notes)');
$courseworkArtifacts = [
    ['personal_1_frontend_probe', '个人作业', '个人作业1 Web前端初探', '针对任意网页调研 GET/POST 请求并截图请求与响应；使用 JQuery 触发事件并记录前后状态；完成一个浏览器插件并说明功能与代码。', 'docs/coursework/personal-1-web-request-jquery-plugin.md', 'todo', 1, '个人独立提交，项目中只保留要求、证据模板和可复用页面入口。'],
    ['personal_2_frontend_design', '个人作业', '个人作业2 Web前端设计', '使用 Axure 等软件设计一个页面；为团队选择前台与后台模板；最终按学号姓名打包提交。', 'docs/coursework/personal-2-frontend-design.md', 'todo', 2, '个人独立提交，后续补原型截图和模板选型说明。'],
    ['personal_3_wordpress_trial', '个人作业', '个人作业3 开源建站工具初试文档', '自行安装 WordPress 并做简单页面改造，记录安装步骤、布局设计和最终页面。', 'docs/coursework/personal-3-wordpress-trial.md', 'todo', 3, '个人独立提交，项目不混入 WordPress 运行代码，只留实验文档入口。'],
    ['team_1_requirements', '团队作业', '团队作业1 需求文档', '体育主题 2026 世界杯；明确队名；前台包含团队、个人展示和留言；后台包含资料编辑、信息发布；数据库表数不少于 10。', 'docs/requirements.md', 'ready', 10, '已映射到 PHP + MySQL 项目功能和数据表。'],
    ['team_2_design', '团队作业', '团队作业2 设计文档', '说明前台、后台模板与页面结构，给出数据库 ER 设计、模块设计和交互流程。', 'docs/design.md', 'ready', 20, '设计文档与当前 MVC 目录、页面和 MySQL 表结构一致。'],
    ['team_3_implementation', '团队作业', '团队作业3 实现文档', '记录关键实现：登录注册、问卷、球队球员、赛程晋级图、新闻同步翻译、AI 创作、后台 CRUD。', 'docs/implementation.md', 'ready', 30, '对应 app、views、scripts 和 database 目录。'],
    ['team_4_user_manual', '团队作业', '团队作业4 用户手册', '说明普通用户、管理员、新闻刷新、AI 创作、数据导入和偏好问卷的使用流程。', 'docs/user-manual.md', 'ready', 40, '可直接作为验收操作说明。'],
    ['team_5_deployment', '团队作业', '团队作业5 部署文档', '说明 PHP、MySQL、XAMPP、.env、导入脚本、启动命令、部署验证页面和常见故障处理。', 'docs/deployment.md', 'ready', 50, '部署流程与本机 D:\\worldcup-ai-universe 保持一致。'],
    ['team_6_presentation', '团队作业', '团队作业6 项目展示 PPT', '展示项目概述、分工、数据库设计、代码模块、页面演示、数据校验、部署方式和答辩分工。', 'docs/presentation-outline.md', 'ready', 60, '提供 PPT 大纲，后续可按小组姓名和截图替换。'],
    ['coursework_traceability', '课程设计', '老师要求映射与提交清单', '集中保存图片中 1/2/3 阶段个人作业、团队 1-6 作业、Git 协作和最终提交命名要求。', 'docs/coursework-traceability.md', 'ready', 90, '用于防止漏交，页面 /coursework 可查看。'],
];
foreach ($courseworkArtifacts as $artifact) {
    $artifactStmt->execute($artifact);
}

$adminUsername = (string) env('ADMIN_USERNAME', 'admin');
$adminEmail = (string) env('ADMIN_EMAIL', 'admin@example.com');
$adminPassword = (string) env('ADMIN_PASSWORD', 'ChangeMe2026!');
$adminStmt = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1');
$adminStmt->execute([$adminUsername, $adminEmail]);
$adminId = (int) ($adminStmt->fetchColumn() ?: 0);
if (!$adminId) {
    $pdo->prepare('INSERT INTO users (username, email, password_hash, display_name, survey_completed_at) VALUES (?, ?, ?, ?, NOW())')
        ->execute([$adminUsername, $adminEmail, password_hash($adminPassword, PASSWORD_DEFAULT), '管理员']);
    $adminId = (int) $pdo->lastInsertId();
}
$roleId = (int) $pdo->query("SELECT id FROM roles WHERE name = 'admin' LIMIT 1")->fetchColumn();
$pdo->prepare('INSERT IGNORE INTO user_roles (user_id, role_id) VALUES (?, ?)')->execute([$adminId, $roleId]);

echo "Install complete.\n";
echo "Admin: {$adminUsername}\n";
echo "Next: php scripts/import_collected_data.php && php scripts/fill_player_name_transliterations.php && php scripts/sync_news.php\n";
