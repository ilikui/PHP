<?php
declare(strict_types=1);

namespace Learning\Logging;

final class Logger
{
    public function __construct(private readonly string $path) {}

    /** @param array<string, scalar|null> $context */
    public function log(string $level, string $message, array $context = []): void
    {
        $level = strtoupper(trim($level));
        if (!in_array($level, ['DEBUG', 'INFO', 'WARNING', 'ERROR'], true)) throw new \InvalidArgumentException('Unsupported log level.');
        $dir = dirname($this->path);
        if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) throw new \RuntimeException('Cannot create log directory.');
        $record = ['timestamp' => gmdate('c'), 'level' => $level, 'message' => $message, 'context' => $context];
        $json = json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR);
        if (file_put_contents($this->path, $json . PHP_EOL, FILE_APPEND | LOCK_EX) === false) throw new \RuntimeException('Unable to write log record.');
    }
}
