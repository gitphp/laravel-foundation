<?php

declare(strict_types=1);

namespace Sunny\Foundation\Http\Middleware;

use Closure;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Sunny\Foundation\Logging\ActivityLogger;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class LogApiRequest
{
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

    private function write(Request $request, int $status, int $started): void
    {
        $path = $request->path();
        $milliseconds = (hrtime(true) - $started) / 1_000_000;

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
