<?php

declare(strict_types=1);

/**
 * API 访问日志中间件
 *
 * @package     Sunny\Foundation\Http\Middleware
 * @license     MIT License (MIT)
 * @author      Sunny Yang <githup@163.com> QQ4873473
 * @copyright   Copyright (c) 2026 Sunny Yang. All rights reserved.
 * @link        http://www.budff.com
 */

namespace Sunny\Foundation\Http\Middleware;

use Closure;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Sunny\Foundation\Logging\ActivityLogger;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * API 访问日志中间件
 */
final class LogApiRequest
{
    /**
     * 记录匹配路径的请求。异常仍会继续向外抛出。
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->shouldLog($request)) {
            return $next($request);
        }

        $started = hrtime(true);

        try {
            $response = $next($request);
            $status = $response->getStatusCode();
        } catch (HttpResponseException $exception) {
            $this->write($request, $exception->getResponse()->getStatusCode(), $started);

            throw $exception;
        } catch (Throwable $exception) {
            $this->write($request, 500, $started);

            throw $exception;
        }

        $this->write($request, $status, $started);

        return $response;
    }

    /**
     * 排除健康检查等路径后，判断是否落在日志路径里。
     */
    private function shouldLog(Request $request): bool
    {
        $except = config('sunny.log.except', []);

        if (is_array($except) && $except !== []) {
            $except = array_values(array_filter($except, 'is_string'));

            if ($except !== [] && $request->is(...$except)) {
                return false;
            }
        }

        $paths = config('sunny.log.paths', ['api/*']);

        if (! is_array($paths) || $paths === []) {
            return false;
        }

        $paths = array_values(array_filter($paths, 'is_string'));

        return $paths !== [] && $request->is(...$paths);
    }

    /**
     * 写入方法、路径、状态码、耗时和 IP，不记录请求体。
     */
    private function write(Request $request, int $status, int $started): void
    {
        $path = $request->path();
        $milliseconds = (hrtime(true) - $started) / 1_000_000;

        // 只记方法、路径、状态、耗时和 IP，不记请求体。用户编号来自属性，避免触发 session 守卫。
        app(ActivityLogger::class)->info('http.request', [
            'method' => $request->method(),
            'path' => $path === '/' ? '/' : '/'.$path,
            'status' => $status,
            'duration_ms' => round($milliseconds, 2),
            'ip' => $request->ip(),
            'user_id' => $request->attributes->get('sunny_user_id'),
        ]);
    }
}

