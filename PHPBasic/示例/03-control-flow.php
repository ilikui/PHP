<?php
declare(strict_types=1);

$score = 86;
if ($score < 0 || $score > 100) {
    $grade = '成绩无效';
} elseif ($score >= 90) {
    $grade = '优秀';
} elseif ($score >= 80) {
    $grade = '良好';
} elseif ($score >= 60) {
    $grade = '及格';
} else {
    $grade = '需要继续努力';
}
echo $grade, PHP_EOL;

$sum = 0;
for ($i = 1; $i <= 100; $i++) {
    $sum += $i;
}
echo '1 到 100 的和：', $sum, PHP_EOL;

foreach (['PHP', 'C', 'Python'] as $index => $language) {
    printf("%d. %s\n", $index + 1, $language);
}

$status = 404;
$message = match ($status) {
    200 => '成功',
    400, 422 => '请求错误',
    401, 403 => '无权访问',
    404 => '资源不存在',
    default => '其他状态',
};
echo $message, PHP_EOL;
