<?php
declare(strict_types=1);

namespace Learning\Tests;

use Learning\Http\JsonResponse;
use PHPUnit\Framework\TestCase;

final class JsonResponseTest extends TestCase
{
    public function testSuccessResponse(): void
    {
        $response = JsonResponse::success(['id' => 7], 201);
        self::assertSame(201, $response->status());
        self::assertSame(['application/json; charset=UTF-8'], [$response->headers()['Content-Type']]);
        self::assertSame(['data' => ['id' => 7]], json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR));
    }
    public function testErrorResponse(): void
    {
        $response = JsonResponse::error('validation_error', 'Invalid input.', 422, ['fields' => ['title' => ['required']]]);
        self::assertSame(422, $response->status());
        self::assertSame('validation_error', json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR)['error']['code']);
    }
}
