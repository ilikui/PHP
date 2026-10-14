<?php
declare(strict_types=1);

$pdo = new PDO('sqlite:' . __DIR__ . '/learning.sqlite', options: [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
$pdo->exec('CREATE TABLE IF NOT EXISTS books (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT NOT NULL, author TEXT NOT NULL, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)');

$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
$page = is_int($page) && $page > 0 ? min($page, 1000000) : 1;
$pageSize = 10;
$offset = ($page - 1) * $pageSize;

$total = (int) $pdo->query('SELECT COUNT(*) FROM books')->fetchColumn();

$stmt = $pdo->prepare('SELECT id, title, author FROM books ORDER BY id DESC LIMIT :limit OFFSET :offset');
$stmt->bindValue(':limit', $pageSize, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

echo json_encode([
    'page' => $page,
    'page_size' => $pageSize,
    'total' => $total,
    'total_pages' => (int) ceil($total / $pageSize),
    'items' => $stmt->fetchAll(),
], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), PHP_EOL;
