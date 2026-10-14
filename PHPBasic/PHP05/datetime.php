<?php
declare(strict_types=1);

$utc = new DateTimeImmutable('now', new DateTimeZone('UTC'));
$tokyo = $utc->setTimezone(new DateTimeZone('Asia/Tokyo'));

echo 'UTC：', $utc->format(DateTimeInterface::ATOM), PHP_EOL;
echo '东京：', $tokyo->format(DateTimeInterface::ATOM), PHP_EOL;

$start = new DateTimeImmutable('2026-10-01 09:00:00', new DateTimeZone('Asia/Tokyo'));
$end = new DateTimeImmutable('2026-10-14 18:30:00', new DateTimeZone('Asia/Tokyo'));
$interval = $start->diff($end);
printf("相隔 %d 天 %d 小时 %d 分钟\n", $interval->days, $interval->h, $interval->i);

// 对用户提供的日期应捕获解析错误，并明确业务时区。
try {
    $date = new DateTimeImmutable('2026-12-31', new DateTimeZone('Asia/Tokyo'));
    echo $date->modify('+1 day')->format('Y-m-d'), PHP_EOL;
} catch (DateMalformedStringException $exception) {
    echo '日期格式无效。', PHP_EOL;
}
