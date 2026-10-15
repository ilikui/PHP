<?php
declare(strict_types=1);

namespace Learning\Tests;

use Learning\Validation\InputValidator;
use PHPUnit\Framework\TestCase;

final class InputValidatorTest extends TestCase
{
    public function testAcceptsValidInput(): void
    {
        $validator = new InputValidator();
        self::assertSame([], $validator->validate(['title' => 'PHP', 'page_size' => '20'], ['title' => ['required', 'string', 'max:120'], 'page_size' => ['integer', 'min:1', 'max:100']]));
    }
    public function testReturnsFieldErrors(): void
    {
        $validator = new InputValidator();
        $errors = $validator->validate(['title' => '', 'email' => 'not-an-email', 'page_size' => '999'], ['title' => ['required'], 'email' => ['email'], 'page_size' => ['integer', 'max:100']]);
        self::assertArrayHasKey('title', $errors);
        self::assertArrayHasKey('email', $errors);
        self::assertArrayHasKey('page_size', $errors);
    }
}
