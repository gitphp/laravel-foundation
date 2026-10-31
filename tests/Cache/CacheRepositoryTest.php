<?php

declare(strict_types=1);

/**
 * 缓存测试
 *
 * @package     Sunny\Foundation\Tests\Cache
 * @license     MIT License (MIT)
 * @author      Sunny Yang <githup@163.com> QQ4873473
 * @copyright   Copyright (c) 2026 Sunny Yang. All rights reserved.
 * @link        http://www.budff.com
 */

namespace Sunny\Foundation\Tests\Cache;

use Sunny\Foundation\Cache\CacheRepository;
use Sunny\Foundation\Tests\TestCase;

/**
 * 缓存测试
 */
class CacheRepositoryTest extends TestCase
{
    /**
     * 缓存键应带前缀，重复读取不再执行闭包。
     */
    public function test_it_prefixes_keys_and_remembers_values(): void
    {
        $cache = $this->app->make(CacheRepository::class);
        $calls = 0;

        $first = $cache->remember('answer', function () use (&$calls): int {
            $calls++;

            return 42;
        });
        $second = $cache->remember('answer', function () use (&$calls): int {
            $calls++;

            return 1;
        });

        $this->assertSame(42, $first);
        $this->assertSame(42, $second);
        $this->assertSame(1, $calls);
        $this->assertTrue(cache()->has('sunny:answer'));
        $this->assertTrue($cache->forget('answer'));
        $this->assertFalse($cache->has('answer'));
    }
}

