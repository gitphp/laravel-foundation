<?php

declare(strict_types=1);

/**
 * 配置读取测试
 *
 * @package     Sunny\Foundation\Tests\Config
 * @license     MIT License (MIT)
 * @author      Sunny Yang <githup@163.com> QQ4873473
 * @copyright   Copyright (c) 2026 Sunny Yang. All rights reserved.
 * @link        http://www.budff.com
 */

namespace Sunny\Foundation\Tests\Config;

use Sunny\Foundation\Tests\TestCase;

/**
 * 配置读取测试
 */
class ConfigRepositoryTest extends TestCase
{
    /**
     * 应能按类型读出默认配置。
     */
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

