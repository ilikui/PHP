<?php
declare(strict_types=1);

// 本示例只读写当前脚本目录下固定的 data.txt，不使用用户提供的路径。
$path = __DIR__ . DIRECTORY_SEPARATOR . 'data.txt';
$content = "PHP 文件读写示例" . PHP_EOL . "第二行" . PHP_EOL;

$bytes = file_put_contents($path, $content, LOCK_EX);
if ($bytes === false) {
    throw new RuntimeException('写入文件失败，请检查目录权限。');
}

$readBack = file_get_contents($path);
if ($readBack === false) {
    throw new RuntimeException('读取文件失败。');
}

echo $readBack;
printf("写入字节数：%d\n", $bytes);
