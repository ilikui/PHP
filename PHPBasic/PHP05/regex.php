<?php
declare(strict_types=1);

$text = '联系邮箱：dev@example.com，备用：support@example.org';
$matched = preg_match_all('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', $text, $matches);

if ($matched === false) {
    throw new RuntimeException('正则表达式执行失败。');
}

foreach ($matches[0] as $candidate) {
    $valid = filter_var($candidate, FILTER_VALIDATE_EMAIL) !== false;
    printf("%s：%s\n", $candidate, $valid ? '格式有效' : '格式无效');
}

$normalized = preg_replace('/\s+/u', ' ', trim($text));
if ($normalized === null) {
    throw new RuntimeException('文本替换失败。');
}
echo $normalized, PHP_EOL;
