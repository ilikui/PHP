<?php
declare(strict_types=1);

$unitPrice = 19.9;
$quantity = 3;
$total = $unitPrice * $quantity;

echo "小计：", number_format($total, 2), PHP_EOL;

$isMember = true;
$hasCoupon = false;
if ($isMember || $hasCoupon) {
    echo "满足折扣条件", PHP_EOL;
}

$inputName = $_GET['name'] ?? '访客'; // CLI 下通常使用默认值
if (!is_string($inputName)) {
    $inputName = '访客';
}
echo '你好，', $inputName, PHP_EOL;

// 字符串连接使用 .；外部输入显示到 HTML 时应使用 htmlspecialchars。
$greeting = 'Hello, ' . 'PHP';
echo $greeting, PHP_EOL;
