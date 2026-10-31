<?php

declare(strict_types=1);

namespace Sunny\Foundation\Auth\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Sunny\Foundation\Enums\ResponseCode;
use Sunny\Foundation\Http\Responses\ApiResponse;
use Symfony\Component\HttpFoundation\Response;

final class AuthenticateJwt
{
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

        $request->attributes->set('sunny_user_id', $user->getAuthIdentifier());
        Auth::shouldUse($guardName);

        return $next($request);
    }
}
