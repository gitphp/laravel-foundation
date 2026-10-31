<?php

declare(strict_types=1);

/**
 * 按路径强制 JSON
 *
 * 只改配置里列出的路径，默认是 api/*，普通页面的 Accept 头保持不变。
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
use Symfony\Component\HttpFoundation\Response;

/**
 * 按配置的路径把请求改成只接受 JSON。
 */
final class ForceJsonResponse
{
    /**
     * 匹配到的请求改为只接受 application/json。
     */
    public function handle(Request $request, Closure $next): Response
    {
        $patterns = config('sunny.middleware.json_paths', ['api/*']);

        if (is_array($patterns) && $patterns !== []) {
            $patterns = array_values(array_filter($patterns, 'is_string'));

            // 只有命中的路径才改 Accept，页面请求保持原来的响应格式。
            if ($patterns !== [] && $request->is(...$patterns)) {
                $request->headers->set('Accept', 'application/json');
            }
        }

        return $next($request);
    }
}
