<?php
declare(strict_types=1);

$books = [
    ['title' => 'Clean Code', 'rating' => 9.0],
    ['title' => 'The Pragmatic Programmer', 'rating' => 9.2],
    ['title' => 'PHP Guide', 'rating' => 8.5],
];

foreach ($books as $book) {
    printf("%s：%.1f\n", $book['title'], $book['rating']);
}

$ratings = array_column($books, 'rating');
printf("平均评分：%.2f\n", array_sum($ratings) / count($ratings));

$text = trim('  Learn PHP safely  ');
echo strtoupper($text), PHP_EOL;
var_dump(str_contains($text, 'PHP'));

// 缺失键或 null 值都可以使用 ?? 提供默认值。
$user = ['name' => 'Kai', 'nickname' => null];
echo $user['nickname'] ?? $user['name'], PHP_EOL;

// 将文本放入 HTML 时要做输出编码，而非直接拼接原始输入。
$untrusted = '<script>alert(1)</script>';
echo htmlspecialchars($untrusted, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), PHP_EOL;
