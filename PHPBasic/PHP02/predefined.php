<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');

// 不直接 dump 整个 $_SERVER，避免暴露环境变量、路径或代理信息。
$serverInfo = [
    'PHP_VERSION' => PHP_VERSION,
    'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
    'SERVER_PROTOCOL' => $_SERVER['SERVER_PROTOCOL'] ?? 'N/A',
    'SCRIPT_NAME' => $_SERVER['SCRIPT_NAME'] ?? basename(__FILE__),
];

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>PHP 预定义变量</title>
</head>
<body>
  <h1>预定义变量示例</h1>
  <p>只展示经过挑选的字段，不输出完整的服务器环境信息。</p>
  <dl>
    <?php foreach ($serverInfo as $key => $value): ?>
      <dt><?= e($key) ?></dt>
      <dd><?= e($value) ?></dd>
    <?php endforeach; ?>
  </dl>
</body>
</html>
