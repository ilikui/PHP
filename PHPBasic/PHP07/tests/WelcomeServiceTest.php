<?php
declare(strict_types=1);

namespace Learning\Tests;

use Learning\Contract\Notifier;
use Learning\Exception\InvalidUserException;
use Learning\Service\WelcomeService;
use PHPUnit\Framework\TestCase;

final class WelcomeServiceTest extends TestCase
{
    public function testSendsWelcomeMessage(): void
    {
        $notifier = new class implements Notifier {
            public array $sent = [];

            public function send(string $recipient, string $message): void
            {
                $this->sent[] = [$recipient, $message];
            }
        };

        $service = new WelcomeService($notifier);
        $service->welcome(' 小林 ', 'lin@example.com');

        self::assertSame([['lin@example.com', '你好，小林！欢迎学习 PHP。']], $notifier->sent);
    }

    public function testRejectsEmptyName(): void
    {
        $notifier = new class implements Notifier {
            public function send(string $recipient, string $message): void
            {
                self::fail('非法输入时不应发送消息。');
            }
        };

        $this->expectException(InvalidUserException::class);
        (new WelcomeService($notifier))->welcome(' ', 'lin@example.com');
    }

    public function testRejectsInvalidEmail(): void
    {
        $notifier = new class implements Notifier {
            public function send(string $recipient, string $message): void
            {
                self::fail('非法输入时不应发送消息。');
            }
        };

        $this->expectException(InvalidUserException::class);
        (new WelcomeService($notifier))->welcome('小林', 'not-an-email');
    }
}
