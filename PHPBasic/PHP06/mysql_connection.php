<?php
declare(strict_types=1);

// 先设置环境变量：DB_HOST、DB_PORT、DB_NAME、DB_USER、DB_PASSWORD。
// 不要把生产环境密码硬编码或提交到 Git。
$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: '3306';
$database = getenv('DB_NAME') ?: 'php_learning';
$username = getenv('DB_USER');
$password = getenv('DB_PASSWORD');

if ($username === false || $password === false) {
    throw new RuntimeException('请先设置 DB_USER 与 DB_PASSWORD 环境变量。');
}

$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $database);
$pdo = new PDO($dsn, $username, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);

echo 'MySQL PDO 连接成功。', PHP_EOL;
