<?php
declare(strict_types=1);

// 标量类型、数组、null 与类型检查。
$name = 'PHP';
$count = 3;
$price = 12.5;
$enabled = true;
$tags = ['web', 'backend'];
$optionalValue = null;

foreach (compact('name', 'count', 'price', 'enabled', 'tags', 'optionalValue') as $key => $value) {
    printf("%s (%s): ", $key, get_debug_type($value));
    var_export($value);
    echo PHP_EOL;
}

// 严格比较可避免意外的隐式类型转换。
var_dump(5 == '5');  // true
var_dump(5 === '5'); // false

function formatUser(string $name, int $age): string
{
    if ($age < 0 || $age > 150) {
        throw new InvalidArgumentException('年龄必须在 0 到 150 之间。');
    }

    return sprintf('%s，%d 岁', $name, $age);
}

echo formatUser('小林', 28), PHP_EOL;
