<?php

declare(strict_types=1);

/**
 * Sunny 门面
 *
 * @package     Sunny\Foundation\Facades
 * @license     MIT License (MIT)
 * @author      Sunny Yang <githup@163.com> QQ4873473
 * @copyright   Copyright (c) 2026 Sunny Yang. All rights reserved.
 * @link        http://www.budff.com
 */

namespace Sunny\Foundation\Facades;

use Illuminate\Support\Facades\Facade;
use Sunny\Foundation\SunnyManager;

/**
 * 静态代理到 SunnyManager。
 *
 * @method static \Sunny\Foundation\Config\ConfigRepository config()
 * @method static \Sunny\Foundation\Cache\CacheRepository cache()
 * @method static \Sunny\Foundation\Auth\JwtManager jwt()
 * @method static \Sunny\Foundation\Logging\ActivityLogger logger()
 *
 * @see SunnyManager
 */
final class Sunny extends Facade
{
    /**
     * 容器中的绑定名称。
     */
    protected static function getFacadeAccessor(): string
    {
        return 'sunny';
    }
}
