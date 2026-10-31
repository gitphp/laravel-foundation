<?php

declare(strict_types=1);

/**
 * 全局助手函数
 *
 * 函数名都以 sunny_ 开头，避免和项目里已有的助手重名。调用前会检查 function_exists。
 *
 * @package     Sunny\Foundation
 * @license     MIT License (MIT)
 * @author      Sunny Yang <githup@163.com> QQ4873473
 * @copyright   Copyright (c) 2026 Sunny Yang. All rights reserved.
 * @link        http://www.budff.com
 */

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
    /**
     * 取得工具入口。只能在 Laravel 应用里调用。
     */
    function sunny(): SunnyManager
    {
        if (! function_exists('app')) {
            throw new RuntimeException('sunny() can only be used inside a Laravel application.');
        }

        return app(SunnyManager::class);
    }
}

if (! function_exists('sunny_config')) {
    /**
     * 读取 sunny 配置。不传键名时返回全部配置。
     */
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
    /**
     * 取得带前缀的缓存仓库。
     */
    function sunny_cache(): CacheRepository
    {
        return sunny()->cache();
    }
}

if (! function_exists('sunny_request_id')) {
    /**
     * 当前请求 ID。中间件尚未写入或没有请求时返回 null。
     */
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
    /**
     * 按前后保留位数脱敏字符串。
     */
    function sunny_mask(string $value, int $visibleStart = 3, int $visibleEnd = 4, string $mask = '*'): string
    {
        return Str::mask($value, $visibleStart, $visibleEnd, $mask);
    }
}

if (! function_exists('sunny_tree')) {
    /**
     * 把带 parent_id 的扁平列表转成树。
     */
    function sunny_tree(array $items, string $id = 'id', string $parentId = 'parent_id', string $children = 'children'): array
    {
        return Arr::tree($items, $id, $parentId, $children);
    }
}

if (! function_exists('sunny_yuan')) {
    /**
     * 分转元。
     */
    function sunny_yuan(int|float|string $fen, int $scale = 2): string
    {
        return Number::yuan($fen, $scale);
    }
}

if (! function_exists('sunny_fen')) {
    /**
     * 元转分，结果为整数形式的字符串。
     */
    function sunny_fen(int|float|string $yuan): string
    {
        return Number::fen($yuan);
    }
}

if (! function_exists('sunny_date')) {
    /**
     * 格式化日期。不传时间时使用当前时间。
     */
    function sunny_date(DateTimeInterface|string|int|null $value = null, DateFormat|string $format = DateFormat::DateTime, ?string $timezone = null): string
    {
        $moment = $value === null ? DateTime::now($timezone) : $value;

        return DateTime::format($moment, $format, $timezone);
    }
}

if (! function_exists('sunny_success')) {
    /**
     * 返回成功的 JSON 响应。
     */
    function sunny_success(mixed $data = null, string $message = '', ResponseCode|int $code = ResponseCode::Ok, int $status = 200): JsonResponse
    {
        return ApiResponse::success($data, $message, $code, $status);
    }
}

if (! function_exists('sunny_error')) {
    /**
     * 返回失败的 JSON 响应。
     */
    function sunny_error(string $message = '', ResponseCode|int $code = ResponseCode::Fail, mixed $errors = null, ?int $status = null): JsonResponse
    {
        return ApiResponse::error($message, $code, $errors, $status);
    }
}

if (! function_exists('sunny_paginate')) {
    /**
     * 返回分页 JSON。列表在 data，页码在 meta。
     */
    function sunny_paginate(LengthAwarePaginator $paginator, string $message = '', array $meta = []): JsonResponse
    {
        return ApiResponse::paginate($paginator, $message, $meta);
    }
}

if (! function_exists('sunny_log')) {
    /**
     * 写入本包日志通道。password、token 等字段会被打码。
     */
    function sunny_log(string $message, array $context = [], string $level = 'info'): void
    {
        sunny()->logger()->log($level, $message, $context);
    }
}
