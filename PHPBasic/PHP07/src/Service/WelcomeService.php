<?php
declare(strict_types=1);

namespace Learning\Service;

use Learning\Contract\Notifier;
use Learning\Exception\InvalidUserException;

final class WelcomeService
{
    public function __construct(private Notifier $notifier)
    {
    }

    public function welcome(string $name, string $email): void
    {
        $name = trim($name);
        $email = trim($email);

        if ($name === '') {
            throw new InvalidUserException('姓名不能为空。');
        }
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidUserException('邮箱格式无效。');
        }

        $this->notifier->send($email, sprintf('你好，%s！欢迎学习 PHP。', $name));
    }
}
