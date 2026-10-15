<?php
declare(strict_types=1);

namespace Learning\Tests;

use Learning\Config\Environment;
use PHPUnit\Framework\TestCase;

final class EnvironmentTest extends TestCase
{
    protected function tearDown(): void { putenv('PHP11_TEST_FLAG'); putenv('PHP11_TEST_INT'); }
    public function testReadsDefaultsAndParsesTypes(): void
    {
        $env = new Environment(['APP_ENV' => 'test', 'FEATURE' => true, 'PORT' => 8080]);
        self::assertSame('test', $env->get('APP_ENV'));
        self::assertTrue($env->bool('FEATURE'));
        self::assertSame(8080, $env->int('PORT', 80));
        self::assertSame('fallback', $env->get('MISSING', 'fallback'));
    }
    public function testRealEnvironmentOverridesDefaults(): void
    {
        putenv('PHP11_TEST_FLAG=false');
        $env = new Environment(['PHP11_TEST_FLAG' => true]);
        self::assertFalse($env->bool('PHP11_TEST_FLAG', true));
    }
}
