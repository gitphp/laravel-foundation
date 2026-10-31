<?php

declare(strict_types=1);

/**
 * 扩展包测试基类
 *
 * @package     Sunny\Foundation\Tests
 * @license     MIT License (MIT)
 * @author      Sunny Yang <githup@163.com> QQ4873473
 * @copyright   Copyright (c) 2026 Sunny Yang. All rights reserved.
 * @link        http://www.budff.com
 */

namespace Sunny\Foundation\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Sunny\Foundation\FoundationServiceProvider;

/**
 * 扩展包测试基类
 */
abstract class TestCase extends Orchestra
{
    /**
     * 每个测试使用固定的 JWT 密钥、签发方和数组缓存。
     */
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.timezone' => 'UTC',
            'sunny.jwt.secret' => str_repeat('s', 48),
            'sunny.jwt.issuer' => 'https://sunny.test',
            'sunny.jwt.audience' => null,
            'sunny.jwt.algo' => 'HS256',
            'sunny.jwt.leeway' => 0,
            'sunny.cache.store' => 'array',
        ]);
    }

    /**
     * 在服务提供者启动前把缓存和日志改成不落盘的实现。
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('cache.default', 'array');
        $app['config']->set('logging.channels.sunny', [
            'driver' => 'monolog',
            'handler' => \Monolog\Handler\NullHandler::class,
            'level' => 'debug',
        ]);
    }

    /**
     * 注册本包的服务提供者。
     *
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            FoundationServiceProvider::class,
        ];
    }

    /**
     * 注册 Sunny 门面别名。
     *
     * @return array<string, class-string>
     */
    protected function getPackageAliases($app): array
    {
        return [
            'Sunny' => \Sunny\Foundation\Facades\Sunny::class,
        ];
    }
}

