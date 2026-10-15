<?php
declare(strict_types=1);

namespace Learning\Tests;

use Learning\Logging\Logger;
use PHPUnit\Framework\TestCase;

final class LoggerTest extends TestCase
{
    public function testWritesStructuredJsonLine(): void
    {
        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'php11-logger-' . bin2hex(random_bytes(6));
        $path = $dir . DIRECTORY_SEPARATOR . 'app.log';
        try {
            $logger = new Logger($path);
            $logger->log('info', 'Request completed', ['status' => 200, 'route' => '/health']);
            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            self::assertIsArray($lines);
            self::assertCount(1, $lines);
            $record = json_decode($lines[0], true, 512, JSON_THROW_ON_ERROR);
            self::assertSame('INFO', $record['level']);
            self::assertSame('Request completed', $record['message']);
            self::assertSame(200, $record['context']['status']);
        } finally {
            if (is_file($path)) unlink($path);
            if (is_dir($dir)) rmdir($dir);
        }
    }

    public function testRejectsUnknownLogLevel(): void
    {
        $logger = new Logger(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'php11-invalid.log');
        $this->expectException(\InvalidArgumentException::class);
        $logger->log('TRACE', 'not supported');
    }
}
