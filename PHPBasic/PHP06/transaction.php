<?php
declare(strict_types=1);

$pdo = new PDO('sqlite:' . __DIR__ . '/learning.sqlite', options: [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$pdo->exec('CREATE TABLE IF NOT EXISTS ledger (id INTEGER PRIMARY KEY AUTOINCREMENT, label TEXT NOT NULL, amount INTEGER NOT NULL)');

try {
    $pdo->beginTransaction();

    $insert = $pdo->prepare('INSERT INTO ledger (label, amount) VALUES (:label, :amount)');
    $insert->execute(['label' => '收入', 'amount' => 1000]);
    $insert->execute(['label' => '支出', 'amount' => -200]);

    // 所有操作都成功后才提交。
    $pdo->commit();
    echo '事务已提交。', PHP_EOL;
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // 实际服务应记录异常详情到受控日志，不直接回传给终端用户。
    throw $exception;
}
