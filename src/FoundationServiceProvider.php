<?php

declare(strict_types=1);

/**
 * 服务提供者
 *
 * @package     Sunny\Foundation
 * @license     MIT License (MIT)
 * @author      Sunny Yang <githup@163.com> QQ4873473
 * @copyright   Copyright (c) 2026 Sunny Yang. All rights reserved.
 * @link        http://www.budff.com
 */

namespace Sunny\Foundation;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\ServiceProvider;
use Sunny\Foundation\Auth\JwtGuard;
use Sunny\Foundation\Auth\JwtManager;
use Sunny\Foundation\Auth\JwtUserResolver;
use Sunny\Foundation\Auth\UserResolver;
use Sunny\Foundation\Cache\CacheRepository;
use Sunny\Foundation\Config\ConfigRepository;
use Sunny\Foundation\Logging\ActivityLogger;

/**
 * 服务提供者
 */
class FoundationServiceProvider extends ServiceProvider
{
    public const VERSION = '1.0.0';

    /**
     * 注册配置和容器绑定。这里只做绑定，不注册路由。
     */
    public function register(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__).'/config/sunny.php', 'sunny');

        $this->app->singleton(ConfigRepository::class);
        $this->app->singleton(CacheRepository::class);
        $this->app->singleton(ActivityLogger::class);
        $this->app->singleton(UserResolver::class, JwtUserResolver::class);
        $this->app->singleton(JwtManager::class);
        $this->app->singleton(SunnyManager::class);
        $this->app->singleton('sunny', fn ($app) => $app->make(SunnyManager::class));
    }

    /**
     * 启动翻译、日志通道、守卫、全局中间件和发布资源。
     */
    public function boot(): void
    {
        $this->loadTranslationsFrom(dirname(__DIR__).'/lang', 'sunny');
        $this->registerLogChannel();
        $this->registerGuard();
        $this->registerMiddleware();
        $this->registerAbout();
        $this->registerPublishing();
    }

    /**
     * 应用尚未定义同名通道时，注册按天切割的 sunny 日志。
     */
    protected function registerLogChannel(): void
    {
        $name = (string) config('sunny.log.channel', 'sunny');

        // 应用或测试已经定义同名通道时不覆盖，避免把测试里的空日志换成写文件。
        if ($name === '' || config('logging.channels.'.$name) !== null) {
            return;
        }

        if (preg_match('/^[A-Za-z0-9_-]{1,40}$/', $name) !== 1) {
            throw new \InvalidArgumentException('The sunny log channel name is invalid.');
        }

        config()->set('logging.channels.'.$name, [
            'driver' => 'daily',
            'path' => storage_path('logs/'.$name.'.log'),
            'level' => (string) config('sunny.log.level', 'debug'),
            'days' => max(1, (int) config('sunny.log.days', 14)),
            'replace_placeholders' => true,
        ]);
    }

    /**
     * 注册 sunny-jwt 守卫。应用没有同名 guard 时补上默认配置。
     */
    protected function registerGuard(): void
    {
        $this->app->booted(function (): void {
            $guard = (string) config('sunny.jwt.guard', 'sunny');

            $this->app->make('auth')->extend('sunny-jwt', function ($app): JwtGuard {
                return new JwtGuard(
                    $app,
                    $app->make(JwtManager::class),
                    $app->make(UserResolver::class),
                );
            });

            if (! config('auth.guards.'.$guard)) {
                config()->set('auth.guards.'.$guard, [
                    'driver' => 'sunny-jwt',
                    'provider' => config('sunny.jwt.provider', 'users'),
                ]);
            }
        });
    }

    /**
     * 注册中间件别名，并按配置把全局中间件推进 HTTP 内核。
     */
    protected function registerMiddleware(): void
    {
        $this->app->booted(function (): void {
            $router = $this->app->make('router');

            foreach (config('sunny.middleware.aliases', []) as $alias => $middleware) {
                if (is_string($alias) && $alias !== '' && is_string($middleware) && $middleware !== '') {
                    $router->aliasMiddleware($alias, $middleware);
                }
            }

            // enabled 是真正的布尔值。字符串 "false" 也会被滤成关闭。
            if (! filter_var(config('sunny.middleware.enabled', true), FILTER_VALIDATE_BOOL)) {
                return;
            }

            $kernel = $this->app->make(Kernel::class);

            if (! method_exists($kernel, 'pushMiddleware')) {
                return;
            }

            foreach (config('sunny.middleware.global', []) as $middleware) {
                if (is_string($middleware) && $middleware !== '') {
                    $kernel->pushMiddleware($middleware);
                }
            }
        });
    }

    /**
     * 在 artisan about 中显示本包版本和 JWT 算法。
     */
    protected function registerAbout(): void
    {
        if (! class_exists(AboutCommand::class)) {
            return;
        }

        AboutCommand::add('Sunny Foundation', fn (): array => [
            'Version' => self::VERSION,
            'JWT Algorithm' => (string) config('sunny.jwt.algo', 'HS256'),
            'Global Middleware' => filter_var(config('sunny.middleware.enabled', true), FILTER_VALIDATE_BOOL) ? 'Enabled' : 'Disabled',
        ]);
    }

    /**
     * 在命令行下发布配置和语言包。
     */
    protected function registerPublishing(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            dirname(__DIR__).'/config/sunny.php' => config_path('sunny.php'),
        ], 'sunny-config');

        $this->publishes([
            dirname(__DIR__).'/lang' => $this->app->langPath('vendor/sunny'),
        ], 'sunny-lang');
    }
}

