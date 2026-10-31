<?php

declare(strict_types=1);

/**
 * 请求 ID 中间件
 *
 * 为每个响应写入 X-Request-Id，并放进日志上下文，方便把一次请求的日志串起来。
 *
 * @package     Sunny\Foundation\Http\Middleware
 * @license     MIT License (MIT)
 * @author      Sunny Yang <githup@163.com> QQ4873473
 * @copyright   Copyright (c) 2026 Sunny Yang. All rights reserved.
 * @link        http://www.budff.com
 */

namespace Sunny\Foundation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Sunny\Foundation\Support\Toolkit;
use Symfony\Component\HttpFoundation\Response;

/**
 * 为每个请求分配或保留请求 ID。
 */
final class AssignRequestId
{
    /**
     * 接受客户端传入的请求 ID，格式不合法时重新生成。
     */
    public function handle(Request $request, Closure $next): Response
    {
        $header = (string) config('sunny.middleware.request_id_header', 'X-Request-Id');

        // 请求头名称只允许字母、数字和连字符，避免被写成非法头。
        if (preg_match('/^[A-Za-z0-9-]{1,40}$/', $header) !== 1) {
            $header = 'X-Request-Id';
        }

        $incoming = $request->headers->get($header);

        // 拒绝换行和过短的值，防止请求 ID 被写进日志时造成注入。
        $requestId = is_string($incoming) && preg_match('/^[A-Za-z0-9_-]{8,128}$/', $incoming) === 1
            ? $incoming
            : Toolkit::uuid();

        $request->attributes->set('request_id', $requestId);

        if (method_exists(Log::getFacadeRoot(), 'shareContext')) {
            Log::shareContext(['request_id' => $requestId]);
        }

        $response = $next($request);
        $response->headers->set($header, $requestId);

        return $response;
    }
}
