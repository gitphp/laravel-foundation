<?php

declare(strict_types=1);

namespace Sunny\Foundation;

use Sunny\Foundation\Auth\JwtManager;
use Sunny\Foundation\Cache\CacheRepository;
use Sunny\Foundation\Config\ConfigRepository;
use Sunny\Foundation\Logging\ActivityLogger;

final class SunnyManager
{
    public function config(): ConfigRepository
    {
        return app(ConfigRepository::class);
    }

    public function cache(): CacheRepository
    {
        return app(CacheRepository::class);
    }

    public function jwt(): JwtManager
    {
        return app(JwtManager::class);
    }

    public function logger(): ActivityLogger
    {
        return app(ActivityLogger::class);
    }
}
