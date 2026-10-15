<?php
declare(strict_types=1);

namespace Learning\Config;

final class Environment
{
    /** @param array<string, string|int|bool|null> $defaults */
    public function __construct(private readonly array $defaults = []) {}

    public function get(string $key, ?string $fallback = null): ?string
    {
        $value = getenv($key);
        if ($value !== false) return $value;
        if (array_key_exists($key, $this->defaults)) {
            $default = $this->defaults[$key];
            if ($default === null) return $fallback;
            if (is_bool($default)) return $default ? 'true' : 'false';
            return (string) $default;
        }
        return $fallback;
    }

    public function bool(string $key, bool $fallback = false): bool
    {
        $value = $this->get($key);
        if ($value === null) return $fallback;
        $parsed = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        return $parsed ?? $fallback;
    }

    public function int(string $key, int $fallback): int
    {
        $value = $this->get($key);
        if ($value === null) return $fallback;
        $parsed = filter_var($value, FILTER_VALIDATE_INT);
        return $parsed === false ? $fallback : $parsed;
    }
}
