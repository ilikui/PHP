<?php
declare(strict_types=1);

namespace Learning\Contract;

interface Notifier
{
    public function send(string $recipient, string $message): void;
}
