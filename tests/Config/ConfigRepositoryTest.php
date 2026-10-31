<?php

declare(strict_types=1);

namespace Sunny\Foundation\Tests\Config;

use Sunny\Foundation\Tests\TestCase;

class ConfigRepositoryTest extends TestCase
{
    public function test_it_reads_package_configuration(): void
    {
        $config = sunny()->config();

        $this->assertSame(3600, $config->int('jwt.ttl'));
        $this->assertSame('HS256', $config->string('jwt.algo'));
        $this->assertTrue($config->bool('middleware.enabled'));
        $this->assertIsArray($config->array('middleware.global'));
        $this->assertSame(3600, sunny_config('cache.ttl'));
    }
}
