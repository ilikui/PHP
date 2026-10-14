<?php
declare(strict_types=1);

// phpinfo() exposes configuration and environment details.
// Keep this learning endpoint available only from a local browser.
$remoteAddress = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($remoteAddress, ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    exit('For local development only.');
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>PHP 环境信息（仅限本地）</title>
</head>
<body>
  <h1>PHP 环境信息</h1>
  <p>仅在本机调试时使用。完成排查后应删除此页面。</p>
  <?php phpinfo(); ?>
</body>
</html>
