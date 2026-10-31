<?php

declare(strict_types=1);

namespace Sunny\Foundation\Config;

final class ConfigRepository
{
    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $all = config('sunny', []);

        return is_array($all) ? $all : [];
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return config('sunny.'.$key, $default);
    }

    public function has(string $key): bool
    {
        return config()->has('sunny.'.$key);
    }

    public function string(string $key, string $default = ''): string
    {
        $value = $this->get($key, $default);

        return is_scalar($value) ? (string) $value : $default;
    }

    public function int(string $key, int $default = 0): int
    {
        $value = $this->get($key, $default);

        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && preg_match('/^-?\d+$/', $value) === 1) {
            return (int) $value;
        }

        return $default;
    }

    public function bool(string $key, bool $default = false): bool
    {
        $value = $this->get($key, $default);

        if (is_bool($value)) {
            return $value;
        }

        if (! is_scalar($value)) {
            return $default;
        }

        $parsed = filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

        return $parsed ?? $default;
    }

    /**
     * @param  array<mixed>  $default
     * @return array<mixed>
     */
    public function array(string $key, array $default = []): array
    {
        $value = $this->get($key, $default);

        return is_array($value) ? $value : $default;
    }
}
