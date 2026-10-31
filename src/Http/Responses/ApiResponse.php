<?php

declare(strict_types=1);

/**
 * 统一 API 响应
 *
 * @package     Sunny\Foundation\Http\Responses
 * @license     MIT License (MIT)
 * @author      Sunny Yang <githup@163.com> QQ4873473
 * @copyright   Copyright (c) 2026 Sunny Yang. All rights reserved.
 * @link        http://www.budff.com
 */

namespace Sunny\Foundation\Http\Responses;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\JsonResponse;
use Sunny\Foundation\Enums\ResponseCode;

/**
 * 统一 API 响应
 */
final class ApiResponse
{
    /**
     * 只通过静态方法构造响应。
     */
    private function __construct()
    {
    }

    /**
     * 成功响应。中文不会被转成 Unicode 转义。
     */
    public static function success(mixed $data = null, string $message = '', ResponseCode|int $code = ResponseCode::Ok, int $status = 200): JsonResponse
    {
        [$numeric, $resolvedMessage, $resolvedStatus] = self::describe($code, $message, $status, ResponseCode::Ok);

        return self::make($numeric, $resolvedMessage, self::normalize($data), $resolvedStatus);
    }

    /**
     * 失败响应。传入枚举时自动带上对应的 HTTP 状态。
     */
    public static function error(string $message = '', ResponseCode|int $code = ResponseCode::Fail, mixed $errors = null, ?int $status = null): JsonResponse
    {
        [$numeric, $resolvedMessage, $resolvedStatus] = self::describe($code, $message, $status, ResponseCode::Fail);

        return self::make($numeric, $resolvedMessage, null, $resolvedStatus, $errors);
    }

    /**
     * 分页响应。记录放在 data，页码放在 meta。
     *
     * @param  array<string, mixed>  $meta
     */
    public static function paginate(LengthAwarePaginator $paginator, string $message = '', array $meta = []): JsonResponse
    {
        $items = [];

        foreach ($paginator->items() as $item) {
            $items[] = self::normalize($item);
        }

        [$numeric, $resolvedMessage, $resolvedStatus] = self::describe(ResponseCode::Ok, $message, 200, ResponseCode::Ok);

        return self::make($numeric, $resolvedMessage, $items, $resolvedStatus, null, array_merge([
            'current_page' => (int) $paginator->currentPage(),
            'per_page' => (int) $paginator->perPage(),
            'total' => (int) $paginator->total(),
            'last_page' => (int) $paginator->lastPage(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ], $meta));
    }

    /**
     * 把业务码解析成数字码、文案和 HTTP 状态。
     *
     * @return array{0: int, 1: string, 2: int}
     */
    private static function describe(ResponseCode|int $code, string $message, ?int $status, ResponseCode $fallback): array
    {
        if ($code instanceof ResponseCode) {
            return [
                $code->value,
                $message !== '' ? $message : $code->message(),
                $status ?? $code->httpStatus(),
            ];
        }

        $resolvedStatus = $status ?? ($code >= 400 && $code <= 599 ? $code : $fallback->httpStatus());

        return [
            $code,
            $message !== '' ? $message : $fallback->message(),
            $resolvedStatus,
        ];
    }

    /**
     * 组装统一的 JSON 结构。
     */
    private static function make(int $code, string $message, mixed $data, int $status, mixed $errors = null, ?array $meta = null): JsonResponse
    {
        $payload = [
            'code' => $code,
            'message' => $message,
            'data' => $data,
            'request_id' => function_exists('sunny_request_id') ? sunny_request_id() : null,
        ];

        if ($errors !== null) {
            $payload['errors'] = self::normalize($errors);
        }

        if ($meta !== null) {
            $payload['meta'] = $meta;
        }

        // 保留中文原文，不转成 \uXXXX。
        return response()->json($payload, $status, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * 把可转数组的对象收成数组。
     */
    private static function normalize(mixed $data): mixed
    {
        if ($data instanceof Arrayable) {
            return $data->toArray();
        }

        if ($data instanceof \JsonSerializable) {
            return $data->jsonSerialize();
        }

        return $data;
    }
}

