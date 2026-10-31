<?php

declare(strict_types=1);

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Sunny\Foundation\Cache\CacheRepository;
use Sunny\Foundation\Enums\DateFormat;
use Sunny\Foundation\Enums\ResponseCode;
use Sunny\Foundation\Http\Responses\ApiResponse;
use Sunny\Foundation\SunnyManager;
use Sunny\Foundation\Support\Arr;
use Sunny\Foundation\Support\DateTime;
use Sunny\Foundation\Support\Number;
use Sunny\Foundation\Support\Str;

if (! function_exists('sunny')) {
    function sunny(): SunnyManager
    {
        if (! function_exists('app')) {
            throw new RuntimeException('sunny() can only be used inside a Laravel application.');
        }

        return app(SunnyManager::class);
    }
}

if (! function_exists('sunny_config')) {
    function sunny_config(?string $key = null, mixed $default = null): mixed
    {
        $config = sunny()->config();

        if ($key === null) {
            return $config->all();
        }

        return $config->get($key, $default);
    }
}

if (! function_exists('sunny_cache')) {
    function sunny_cache(): CacheRepository
    {
        return sunny()->cache();
    }
}

if (! function_exists('sunny_request_id')) {
    function sunny_request_id(): ?string
    {
        if (! function_exists('request') || ! app()->bound('request')) {
            return null;
        }

        $requestId = request()->attributes->get('request_id');

        return is_string($requestId) && $requestId !== '' ? $requestId : null;
    }
}

if (! function_exists('sunny_mask')) {
    function sunny_mask(string $value, int $visibleStart = 3, int $visibleEnd = 4, string $mask = '*'): string
    {
        return Str::mask($value, $visibleStart, $visibleEnd, $mask);
    }
}

if (! function_exists('sunny_tree')) {
    function sunny_tree(array $items, string $id = 'id', string $parentId = 'parent_id', string $children = 'children'): array
    {
        return Arr::tree($items, $id, $parentId, $children);
    }
}

if (! function_exists('sunny_yuan')) {
    function sunny_yuan(int|float|string $fen, int $scale = 2): string
    {
        return Number::yuan($fen, $scale);
    }
}

if (! function_exists('sunny_fen')) {
    function sunny_fen(int|float|string $yuan): string
    {
        return Number::fen($yuan);
    }
}

if (! function_exists('sunny_date')) {
    function sunny_date(DateTimeInterface|string|int|null $value = null, DateFormat|string $format = DateFormat::DateTime, ?string $timezone = null): string
    {
        $moment = $value === null ? DateTime::now($timezone) : $value;

        return DateTime::format($moment, $format, $timezone);
    }
}

if (! function_exists('sunny_success')) {
    function sunny_success(mixed $data = null, string $message = '', ResponseCode|int $code = ResponseCode::Ok, int $status = 200): JsonResponse
    {
        return ApiResponse::success($data, $message, $code, $status);
    }
}

if (! function_exists('sunny_error')) {
    function sunny_error(string $message = '', ResponseCode|int $code = ResponseCode::Fail, mixed $errors = null, ?int $status = null): JsonResponse
    {
        return ApiResponse::error($message, $code, $errors, $status);
    }
}

if (! function_exists('sunny_paginate')) {
    function sunny_paginate(LengthAwarePaginator $paginator, string $message = '', array $meta = []): JsonResponse
    {
        return ApiResponse::paginate($paginator, $message, $meta);
    }
}

if (! function_exists('sunny_log')) {
    function sunny_log(string $message, array $context = [], string $level = 'info'): void
    {
        sunny()->logger()->log($level, $message, $context);
    }
}
