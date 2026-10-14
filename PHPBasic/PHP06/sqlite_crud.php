<?php
declare(strict_types=1);

$databasePath = __DIR__ . DIRECTORY_SEPARATOR . 'learning.sqlite';
$pdo = new PDO('sqlite:' . $databasePath, options: [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS books (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        author TEXT NOT NULL,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )'
);

// Create：使用占位符绑定所有外部值。
$insert = $pdo->prepare('INSERT INTO books (title, author) VALUES (:title, :author)');
$insert->execute(['title' => 'PHP 实战', 'author' => '示例作者']);
$id = (int) $pdo->lastInsertId();

// Read
$select = $pdo->prepare('SELECT id, title, author, created_at FROM books WHERE id = :id');
$select->execute(['id' => $id]);
$book = $select->fetch();
if ($book === false) {
    throw new RuntimeException('记录不存在。');
}
echo json_encode($book, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), PHP_EOL;

// Update
$update = $pdo->prepare('UPDATE books SET title = :title WHERE id = :id');
$update->execute(['title' => 'PHP 实战（已更新）', 'id' => $id]);

// Delete：演示脚本清理自己创建的示例记录。
$delete = $pdo->prepare('DELETE FROM books WHERE id = :id');
$delete->execute(['id' => $id]);

echo "CRUD 示例执行完成。", PHP_EOL;
