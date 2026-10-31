<?php

declare(strict_types=1);

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

class FoundationServiceProvider extends ServiceProvider
{
    public const VERSION = '1.0.0';

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

    public function boot(): void
    {
        $this->loadTranslationsFrom(dirname(__DIR__).'/lang', 'sunny');
        $this->registerLogChannel();
        $this->registerGuard();
        $this->registerMiddleware();
        $this->registerAbout();
        $this->registerPublishing();
    }

    protected function registerLogChannel(): void
    {
        $name = (string) config('sunny.log.channel', 'sunny');

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

    protected function registerMiddleware(): void
    {
        $this->app->booted(function (): void {
            $router = $this->app->make('router');

            foreach (config('sunny.middleware.aliases', []) as $alias => $middleware) {
                if (is_string($alias) && $alias !== '' && is_string($middleware) && $middleware !== '') {
                    $router->aliasMiddleware($alias, $middleware);
                }
            }

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
