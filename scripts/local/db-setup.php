<?php
/**
 * WorldCup Universe local deployment helper (called by scripts/local/deploy-local.ps1).
 * Creates the application database and its own account on a MariaDB 10.4+ server, idempotently.
 * Every value comes from environment variables, so no password ever appears on a command line:
 *   WCU_DB_HOST, WCU_DB_PORT, WCU_DB_NAME, WCU_DB_USER, WCU_DB_PASS   application connection (from .env)
 *   WCU_ADMIN_USER, WCU_ADMIN_PASS                                     server administrator (only to create them)
 * Prints one JSON line: {"ok":true,"version":"...","created":bool,"schema":bool} or {"ok":false,"error":"..."}.
 * Existing databases, tables and rows are never dropped or altered.
 */
declare(strict_types=1);

$e = static fn(string $k, string $d = ''): string => (string)(getenv($k) === false ? $d : getenv($k));
$out = static function (array $data, int $code): never { echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), "\n"; exit($code); };
$host = $e('WCU_DB_HOST', '127.0.0.1'); $port = (int)$e('WCU_DB_PORT', '3306');
$db = $e('WCU_DB_NAME'); $user = $e('WCU_DB_USER'); $pass = $e('WCU_DB_PASS');
foreach (['database' => $db, 'user' => $user] as $what => $v) {
    if (!preg_match('/^[A-Za-z0-9_]{1,64}$/', $v)) { $out(['ok' => false, 'error' => "invalid $what name in .env (letters, digits, underscore)"], 1); }
}
$dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
$opts = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 8];
$check = static function (PDO $pdo) use ($out): string {
    $v = (string)$pdo->query('SELECT VERSION()')->fetchColumn();
    // data/install.sql declares long UNIQUE keys that only MariaDB (hash keys, 10.4+) accepts; MySQL 8 fails with 1071.
    if (stripos($v, 'mariadb') === false) { $out(['ok' => false, 'error' => "server is MySQL $v; this project needs MariaDB 10.4+", 'version' => $v], 3); }
    if (version_compare(preg_replace('/-.*/', '', $v), '10.4.0', '<')) { $out(['ok' => false, 'error' => "MariaDB $v is too old; 10.4+ is required", 'version' => $v], 3); }
    return $v;
};
$schema = static function (PDO $pdo) use ($db): bool {
    $q = $pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=? AND TABLE_NAME=?');
    $q->execute([$db, 'migration']);
    return (int)$q->fetchColumn() > 0;
};

// 1. Already set up: the application account reaches its database. Nothing to create.
try {
    $app = new PDO($dsn, $user, $pass, $opts);
    $version = $check($app);
    $q = $app->prepare('SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME=?'); $q->execute([$db]);
    if ((int)$q->fetchColumn() > 0) { $out(['ok' => true, 'version' => $version, 'created' => false, 'schema' => $schema($app)], 0); }
} catch (PDOException $ex) {
    if ((int)$ex->getCode() === 2002 || str_contains($ex->getMessage(), '2002')) {
        $out(['ok' => false, 'error' => "cannot reach MariaDB at $host:$port (" . $ex->getMessage() . ')'], 2);
    }
}

// 2. Create database and account with the administrator connection.
$adminUser = $e('WCU_ADMIN_USER', 'root');
try { $root = new PDO($dsn, $adminUser, $e('WCU_ADMIN_PASS'), $opts); }
catch (PDOException $ex) { $out(['ok' => false, 'error' => "administrator login '$adminUser' failed: " . $ex->getMessage(), 'needAdmin' => true], 4); }
$version = $check($root);
$q = $root->prepare('SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME=?'); $q->execute([$db]);
$existed = (int)$q->fetchColumn() > 0;
$root->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
if ($user !== $adminUser) {
    $pw = $root->quote($pass);
    foreach (['localhost', '127.0.0.1'] as $h) {
        $root->exec("CREATE USER IF NOT EXISTS '$user'@'$h' IDENTIFIED BY $pw");
        $root->exec("ALTER USER '$user'@'$h' IDENTIFIED BY $pw");   // keep the account in step with .env
        $root->exec("GRANT ALL PRIVILEGES ON `$db`.* TO '$user'@'$h'");
    }
    $root->exec('FLUSH PRIVILEGES');
}
$root->exec("USE `$db`");
$out(['ok' => true, 'version' => $version, 'created' => !$existed, 'schema' => $schema($root)], 0);
