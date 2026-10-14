<?php
declare(strict_types=1);

/**
 * 计算平均值；空数组和非法成绩均明确报错。
 *
 * @param list<int|float> $scores
 */
function averageScore(array $scores): float
{
    if ($scores === []) {
        throw new InvalidArgumentException('成绩列表不能为空。');
    }

    foreach ($scores as $score) {
        if ((!is_int($score) && !is_float($score)) || $score < 0 || $score > 100) {
            throw new InvalidArgumentException('每项成绩都必须是 0 到 100 的数字。');
        }
    }

    return array_sum($scores) / count($scores);
}

try {
    printf("平均成绩：%.2f\n", averageScore([80, 90, 100]));
} catch (InvalidArgumentException $exception) {
    // 学习示例可以展示错误；生产环境应统一记录日志并返回安全提示。
    echo '输入错误：', $exception->getMessage(), PHP_EOL;
}
