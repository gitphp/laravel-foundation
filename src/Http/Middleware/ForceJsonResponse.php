<?php

declare(strict_types=1);

namespace Sunny\Foundation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ForceJsonResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $patterns = config('sunny.middleware.json_paths', ['api/*']);

        if (is_array($patterns) && $patterns !== []) {
            $patterns = array_values(array_filter($patterns, 'is_string'));

            if ($patterns !== [] && $request->is(...$patterns)) {
                $request->headers->set('Accept', 'application/json');
            }
        }

        return $next($request);
    }
}
