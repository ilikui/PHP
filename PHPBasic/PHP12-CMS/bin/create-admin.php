<?php
declare(strict_types=1);

use PDO;

if (PHP_SAPI !== 'cli') { http_response_code(404); exit(1); }
$username = trim((string) getenv('CMS_ADMIN_USERNAME'));
$password = (string) getenv('CMS_ADMIN_PASSWORD');
if ($username === '' || mb_strlen($username) > 80 || strlen($password) < 16) {
    fwrite(STDERR, "请设置 CMS_ADMIN_USERNAME，并将 CMS_ADMIN_PASSWORD 设为至少 16 个字符。\n");
    exit(1);
}
$base = dirname(__DIR__);
$dbPath = getenv('CMS_DB_PATH') ?: $base . '/var/cms.sqlite';
$dir = dirname($dbPath);
if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) throw new RuntimeException('无法创建数据库目录。');
$pdo = new PDO('sqlite:' . $dbPath, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$pdo->exec('CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT NOT NULL UNIQUE, password_hash TEXT NOT NULL, created_at TEXT NOT NULL)');
$stmt = $pdo->prepare('INSERT INTO users(username, password_hash, created_at) VALUES(:username, :hash, :created) ON CONFLICT(username) DO UPDATE SET password_hash = excluded.password_hash');
$stmt->execute([':username' => $username, ':hash' => password_hash($password, PASSWORD_DEFAULT), ':created' => gmdate('c')]);
fwrite(STDOUT, "管理员账号已创建/更新。请清除 CMS_ADMIN_PASSWORD 环境变量。\n");
