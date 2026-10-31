<?php

declare(strict_types=1);

namespace Sunny\Foundation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Sunny\Foundation\Support\Toolkit;
use Symfony\Component\HttpFoundation\Response;

final class AssignRequestId
{
    public function handle(Request $request, Closure $next): Response
    {
        $header = (string) config('sunny.middleware.request_id_header', 'X-Request-Id');

        if (preg_match('/^[A-Za-z0-9-]{1,40}$/', $header) !== 1) {
            $header = 'X-Request-Id';
        }

        $incoming = $request->headers->get($header);
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
