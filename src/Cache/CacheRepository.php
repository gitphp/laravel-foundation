<?php

declare(strict_types=1);

namespace Sunny\Foundation\Cache;

use Closure;
use DateInterval;
use DateTimeInterface;
use Illuminate\Contracts\Cache\Repository;

final class CacheRepository
{
    public function store(): Repository
    {
        $name = config('sunny.cache.store');

        if (! is_string($name) || $name === '') {
            return cache()->store();
        }

        return cache()->store($name);
    }

    public function key(string $key): string
    {
        $key = trim($key);

        if ($key === '') {
            throw new \InvalidArgumentException('Cache key must not be empty.');
        }

        $prefix = trim((string) config('sunny.cache.prefix', 'sunny'), ':');

        if ($prefix === '') {
            $prefix = 'sunny';
        }

        return $prefix.':'.$key;
    }

    public function has(string $key): bool
    {
        return $this->store()->has($this->key($key));
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->store()->get($this->key($key), $default);
    }

    public function put(string $key, mixed $value, DateTimeInterface|DateInterval|int|null $ttl = null): bool
    {
        return $this->store()->put($this->key($key), $value, $ttl ?? $this->ttl());
    }

    public function forever(string $key, mixed $value): bool
    {
        return $this->store()->forever($this->key($key), $value);
    }

    public function add(string $key, mixed $value, DateTimeInterface|DateInterval|int|null $ttl = null): bool
    {
        return $this->store()->add($this->key($key), $value, $ttl ?? $this->ttl());
    }

    public function remember(string $key, Closure $callback, DateTimeInterface|DateInterval|int|null $ttl = null): mixed
    {
        return $this->store()->remember($this->key($key), $ttl ?? $this->ttl(), $callback);
    }

    public function rememberForever(string $key, Closure $callback): mixed
    {
        return $this->store()->rememberForever($this->key($key), $callback);
    }

    public function forget(string $key): bool
    {
        return $this->store()->forget($this->key($key));
    }

    public function pull(string $key, mixed $default = null): mixed
    {
        return $this->store()->pull($this->key($key), $default);
    }

    public function increment(string $key, int $value = 1): int|bool
    {
        return $this->store()->increment($this->key($key), $value);
    }

    public function decrement(string $key, int $value = 1): int|bool
    {
        return $this->store()->decrement($this->key($key), $value);
    }

    public function ttl(): int
    {
        return max(1, (int) config('sunny.cache.ttl', 3600));
    }
}
