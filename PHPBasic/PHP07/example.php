<?php
declare(strict_types=1);

require __DIR__ . '/src/Contract/Notifier.php';
require __DIR__ . '/src/Exception/InvalidUserException.php';
require __DIR__ . '/src/Service/WelcomeService.php';

use Learning\Contract\Notifier;
use Learning\Service\WelcomeService;

$consoleNotifier = new class implements Notifier {
    public function send(string $recipient, string $message): void
    {
        printf("发送给 %s：%s\n", $recipient, $message);
    }
};

try {
    (new WelcomeService($consoleNotifier))->welcome('小林', 'lin@example.com');
} catch (Throwable $exception) {
    echo '处理失败：', $exception->getMessage(), PHP_EOL;
}
