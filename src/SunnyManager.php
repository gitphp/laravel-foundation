<?php

declare(strict_types=1);

/**
 * 工具入口
 *
 * 通过 sunny() 或 Sunny 门面取得配置、缓存、JWT 和日志。
 *
 * @package     Sunny\Foundation
 * @license     MIT License (MIT)
 * @author      Sunny Yang <githup@163.com> QQ4873473
 * @copyright   Copyright (c) 2026 Sunny Yang. All rights reserved.
 * @link        http://www.budff.com
 */

namespace Sunny\Foundation;

use Sunny\Foundation\Auth\JwtManager;
use Sunny\Foundation\Cache\CacheRepository;
use Sunny\Foundation\Config\ConfigRepository;
use Sunny\Foundation\Logging\ActivityLogger;

/**
 * 工具入口，转到配置、缓存、JWT 和日志。
 */
final class SunnyManager
{
    /**
     * 读取本包配置。
     */
    public function config(): ConfigRepository
    {
        return app(ConfigRepository::class);
    }

    /**
     * 使用带前缀的缓存。
     */
    public function cache(): CacheRepository
    {
        return app(CacheRepository::class);
    }

    /**
     * 签发、解析和注销 JWT。
     */
    public function jwt(): JwtManager
    {
        return app(JwtManager::class);
    }

    /**
     * 写入 sunny 日志通道。
     */
    public function logger(): ActivityLogger
    {
        return app(ActivityLogger::class);
    }
}
