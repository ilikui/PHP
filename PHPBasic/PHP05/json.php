<?php
declare(strict_types=1);

$data = [
    'title' => 'PHP 学习记录',
    'completed' => true,
    'topics' => ['变量', '函数', 'PDO'],
];

$json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
echo $json, PHP_EOL;

$decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
printf("标题：%s\n", $decoded['title']);

// JSON 来自外部时，仍需校验数据结构与每个字段的类型。
if (!isset($decoded['topics']) || !is_array($decoded['topics'])) {
    throw new UnexpectedValueException('JSON 数据结构不符合预期。');
}
