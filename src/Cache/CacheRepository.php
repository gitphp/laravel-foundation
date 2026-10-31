<?php

declare(strict_types=1);

/**
 * 带前缀的缓存
 *
 * @package     Sunny\Foundation\Cache
 * @license     MIT License (MIT)
 * @author      Sunny Yang <githup@163.com> QQ4873473
 * @copyright   Copyright (c) 2026 Sunny Yang. All rights reserved.
 * @link        http://www.budff.com
 */

namespace Sunny\Foundation\Cache;

use Closure;
use DateInterval;
use DateTimeInterface;
use Illuminate\Contracts\Cache\Repository;

/**
 * 带前缀的缓存
 */
final class CacheRepository
{
    /**
     * 取得配置指定的缓存仓库，未指定时使用应用默认仓库。
     */
    public function store(): Repository
    {
        $name = config('sunny.cache.store');

        if (! is_string($name) || $name === '') {
            return cache()->store();
        }

        return cache()->store($name);
    }

    /**
     * 给缓存键加上前缀，避免和业务缓存冲突。
     */
    public function key(string $key): string
    {
        $key = trim($key);

        if ($key === '') {
            throw new \InvalidArgumentException('Cache key must not be empty.');
        }

        // 去掉前缀两端的冒号，避免拼出 sunny::key 这种双冒号。
        $prefix = trim((string) config('sunny.cache.prefix', 'sunny'), ':');

        if ($prefix === '') {
            $prefix = 'sunny';
        }

        return $prefix.':'.$key;
    }

    /**
     * 判断带前缀的键是否存在。
     */
    public function has(string $key): bool
    {
        return $this->store()->has($this->key($key));
    }

    /**
     * 读取缓存。
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->store()->get($this->key($key), $default);
    }

    /**
     * 写入缓存。未传过期时间时使用配置里的默认秒数。
     */
    public function put(string $key, mixed $value, DateTimeInterface|DateInterval|int|null $ttl = null): bool
    {
        return $this->store()->put($this->key($key), $value, $ttl ?? $this->ttl());
    }

    /**
     * 永久写入缓存。
     */
    public function forever(string $key, mixed $value): bool
    {
        return $this->store()->forever($this->key($key), $value);
    }

    /**
     * 仅在键不存在时写入。
     */
    public function add(string $key, mixed $value, DateTimeInterface|DateInterval|int|null $ttl = null): bool
    {
        return $this->store()->add($this->key($key), $value, $ttl ?? $this->ttl());
    }

    /**
     * 缓存闭包结果，存在时不再执行闭包。
     */
    public function remember(string $key, Closure $callback, DateTimeInterface|DateInterval|int|null $ttl = null): mixed
    {
        return $this->store()->remember($this->key($key), $ttl ?? $this->ttl(), $callback);
    }

    /**
     * 永久缓存闭包结果。
     */
    public function rememberForever(string $key, Closure $callback): mixed
    {
        return $this->store()->rememberForever($this->key($key), $callback);
    }

    /**
     * 删除一个缓存键。
     */
    public function forget(string $key): bool
    {
        return $this->store()->forget($this->key($key));
    }

    /**
     * 读取并删除一个缓存键。
     */
    public function pull(string $key, mixed $default = null): mixed
    {
        return $this->store()->pull($this->key($key), $default);
    }

    /**
     * 把整数缓存加一或加上指定步长。
     */
    public function increment(string $key, int $value = 1): int|bool
    {
        return $this->store()->increment($this->key($key), $value);
    }

    /**
     * 把整数缓存减一或减去指定步长。
     */
    public function decrement(string $key, int $value = 1): int|bool
    {
        return $this->store()->decrement($this->key($key), $value);
    }

    /**
     * 默认缓存秒数，至少为 1。
     */
    public function ttl(): int
    {
        return max(1, (int) config('sunny.cache.ttl', 3600));
    }
}

