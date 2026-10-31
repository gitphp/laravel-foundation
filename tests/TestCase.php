<?php

declare(strict_types=1);

namespace Sunny\Foundation\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Sunny\Foundation\FoundationServiceProvider;

abstract class TestCase extends Orchestra
{
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
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            FoundationServiceProvider::class,
        ];
    }

    /**
     * @return array<string, class-string>
     */
    protected function getPackageAliases($app): array
    {
        return [
            'Sunny' => \Sunny\Foundation\Facades\Sunny::class,
        ];
    }
}
