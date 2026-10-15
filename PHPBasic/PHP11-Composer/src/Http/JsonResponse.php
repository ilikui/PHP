<?php
declare(strict_types=1);

namespace Learning\Http;

final class JsonResponse
{
    /** @param array<string, string> $headers */
    private function __construct(
        private readonly int $status,
        private readonly array $payload,
        private readonly array $headers = []
    ) {}

    /** @param array<string, mixed> $data @param array<string, string> $headers */
    public static function success(array $data, int $status = 200, array $headers = []): self
    {
        if ($status < 200 || $status >= 300) throw new \InvalidArgumentException('Success status must be 2xx.');
        return new self($status, ['data' => $data], $headers);
    }

    /** @param array<string, mixed> $details @param array<string, string> $headers */
    public static function error(string $code, string $message, int $status = 400, array $details = [], array $headers = []): self
    {
        if ($status < 400 || $status > 599) throw new \InvalidArgumentException('Error status must be 4xx or 5xx.');
        return new self($status, ['error' => ['code' => $code, 'message' => $message] + $details], $headers);
    }

    public function status(): int { return $this->status; }
    /** @return array<string, string> */
    public function headers(): array { return ['Content-Type' => 'application/json; charset=UTF-8', 'X-Content-Type-Options' => 'nosniff'] + $this->headers; }
    public function body(): string
    {
        return json_encode($this->payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR);
    }
}
