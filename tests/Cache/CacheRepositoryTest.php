<?php

declare(strict_types=1);

namespace Sunny\Foundation\Tests\Cache;

use Sunny\Foundation\Cache\CacheRepository;
use Sunny\Foundation\Tests\TestCase;

class CacheRepositoryTest extends TestCase
{
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
