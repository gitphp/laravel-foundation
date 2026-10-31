<?php

declare(strict_types=1);

namespace Sunny\Foundation\Facades;

use Illuminate\Support\Facades\Facade;
use Sunny\Foundation\SunnyManager;

/**
 * @method static \Sunny\Foundation\Config\ConfigRepository config()
 * @method static \Sunny\Foundation\Cache\CacheRepository cache()
 * @method static \Sunny\Foundation\Auth\JwtManager jwt()
 * @method static \Sunny\Foundation\Logging\ActivityLogger logger()
 *
 * @see SunnyManager
 */
final class Sunny extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'sunny';
    }
}
