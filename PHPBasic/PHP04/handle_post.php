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

$firstName = $_POST['first_name'] ?? null;
$lastName = $_POST['last_name'] ?? null;
$email = $_POST['email'] ?? null;
$posting = $_POST['posting'] ?? null;

if (
    !is_string($firstName) || !is_string($lastName) ||
    !is_string($email) || !is_string($posting)
) {
    http_response_code(400);
    exit('表单字段格式无效。');
}

$firstName = trim($firstName);
$lastName = trim($lastName);
$email = trim($email);
$posting = trim($posting);

if (
    $firstName === '' || $lastName === '' ||
    mb_strlen($firstName, 'UTF-8') > 80 ||
    mb_strlen($lastName, 'UTF-8') > 80
) {
    http_response_code(422);
    exit('名和姓不能为空，且各自不能超过 80 个字符。');
}
if (strlen($email) > 254 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
    http_response_code(422);
    exit('请输入有效的电子邮箱地址。');
}
if ($posting === '' || mb_strlen($posting, 'UTF-8') > 5000) {
    http_response_code(422);
    exit('发布内容不能为空且不能超过 5000 个字符。');
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>提交结果</title>
</head>
<body>
  <main>
    <h1>内容预览</h1>
    <p><strong>姓名：</strong><?= e($firstName . ' ' . $lastName) ?></p>
    <p><strong>电子邮箱：</strong><?= e($email) ?></p>
    <article><h2>发布内容</h2><p><?= nl2br(e($posting)) ?></p></article>
    <p><a href="posting.html">返回</a></p>
  </main>
</body>
</html>
