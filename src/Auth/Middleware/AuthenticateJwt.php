<?php

declare(strict_types=1);

/**
 * JWT 认证中间件
 *
 * 只接受 Authorization 里的 Bearer 访问令牌。刷新令牌不能用来访问接口。
 *
 * @package     Sunny\Foundation\Auth\Middleware
 * @license     MIT License (MIT)
 * @author      Sunny Yang <githup@163.com> QQ4873473
 * @copyright   Copyright (c) 2026 Sunny Yang. All rights reserved.
 * @link        http://www.budff.com
 */

namespace Sunny\Foundation\Auth\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Sunny\Foundation\Enums\ResponseCode;
use Sunny\Foundation\Http\Responses\ApiResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * 要求请求携带有效的访问令牌。
 */
final class AuthenticateJwt
{
    /**
     * 校验访问令牌，并把本次请求的默认 guard 切换为 sunny。
     */
    public function handle(Request $request, Closure $next): Response
    {
        $guardName = (string) config('sunny.jwt.guard', 'sunny');
        $user = Auth::guard($guardName)->user();

        if ($user === null) {
            return ApiResponse::error(
                message: ResponseCode::Unauthenticated->message(),
                code: ResponseCode::Unauthenticated,
            );
        }

        // 留给访问日志使用，避免日志中间件再去触发会话 guard。
        $request->attributes->set('sunny_user_id', $user->getAuthIdentifier());
        Auth::shouldUse($guardName);

        return $next($request);
    }
}
