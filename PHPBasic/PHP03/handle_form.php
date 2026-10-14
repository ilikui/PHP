<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method Not Allowed');
}

$allowedTitles = ['Mr.', 'Mrs.', 'Ms.', 'Mx.'];
$allowedResponses = ['excellent', 'okay', 'boring'];

$title = $_POST['title'] ?? null;
$name = $_POST['name'] ?? null;
$email = $_POST['email'] ?? null;
$response = $_POST['response'] ?? null;
$comments = $_POST['comments'] ?? '';

if (
    !is_string($title) || !in_array($title, $allowedTitles, true) ||
    !is_string($name) || !is_string($email) ||
    !is_string($response) || !in_array($response, $allowedResponses, true) ||
    !is_string($comments)
) {
    http_response_code(400);
    exit('表单字段格式无效。');
}

$name = trim($name);
$email = trim($email);
$comments = trim($comments);

if ($name === '' || mb_strlen($name, 'UTF-8') > 80) {
    http_response_code(422);
    exit('姓名不能为空且不能超过 80 个字符。');
}
if (strlen($email) > 254 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
    http_response_code(422);
    exit('请输入有效的电子邮箱地址。');
}
if (mb_strlen($comments, 'UTF-8') > 2000) {
    http_response_code(422);
    exit('补充意见不能超过 2000 个字符。');
}

$responseLabels = [
    'excellent' => '很好',
    'okay' => '一般',
    'boring' => '不满意',
];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>反馈结果</title>
</head>
<body>
  <main>
    <h1>感谢你的反馈</h1>
    <dl>
      <dt>称谓</dt><dd><?= e($title) ?></dd>
      <dt>姓名</dt><dd><?= e($name) ?></dd>
      <dt>电子邮箱</dt><dd><?= e($email) ?></dd>
      <dt>体验评价</dt><dd><?= e($responseLabels[$response]) ?></dd>
      <dt>补充意见</dt><dd><?= nl2br(e($comments)) ?></dd>
    </dl>
    <p><a href="feedback.html">返回表单</a></p>
  </main>
</body>
</html>
