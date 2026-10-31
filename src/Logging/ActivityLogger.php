<?php

declare(strict_types=1);

namespace Sunny\Foundation\Logging;

final class ActivityLogger
{
    private const LEVELS = [
        'emergency',
        'alert',
        'critical',
        'error',
        'warning',
        'notice',
        'info',
        'debug',
    ];

    public function log(string $level, string $message, array $context = []): void
    {
        $level = strtolower($level);

        if (! in_array($level, self::LEVELS, true)) {
            throw new \InvalidArgumentException('Unsupported log level ['.$level.'].');
        }

        $context = $this->sanitize($context);

        if (! array_key_exists('request_id', $context) && function_exists('sunny_request_id')) {
            $requestId = sunny_request_id();

            if (is_string($requestId) && $requestId !== '') {
                $context['request_id'] = $requestId;
            }
        }

        $channel = (string) config('sunny.log.channel', 'sunny');
        app('log')->channel($channel)->log($level, $message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    public function notice(string $message, array $context = []): void
    {
        $this->log('notice', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    public function exception(\Throwable $exception, string $message = '', array $context = []): void
    {
        $details = [
            'class' => $exception::class,
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ];

        if (config('sunny.log.trace') === true) {
            $details['trace'] = $exception->getTraceAsString();
        }

        $context['exception'] = $details;
        $this->error($message !== '' ? $message : $exception->getMessage(), $context);
    }

    /**
     * @param  array<mixed>  $context
     * @return array<mixed>
     */
    private function sanitize(array $context, int $depth = 0): array
    {
        $clean = [];

        foreach ($context as $key => $value) {
            if (is_string($key) && $this->redacted($key)) {
                $clean[$key] = '[redacted]';

                continue;
            }

            $clean[$key] = $this->sanitizeValue($value, $depth);
        }

        return $clean;
    }

    private function sanitizeValue(mixed $value, int $depth): mixed
    {
        if ($depth > 6) {
            return '[max-depth]';
        }

        if (is_array($value)) {
            return $this->sanitize($value, $depth + 1);
        }

        if ($value === null || is_bool($value) || is_int($value) || is_float($value)) {
            return $value;
        }

        if (is_string($value)) {
            return strlen($value) > 5000 ? substr($value, 0, 5000).'...' : $value;
        }

        if ($value instanceof \Stringable) {
            return (string) $value;
        }

        if (is_object($value)) {
            return $value::class;
        }

        return get_debug_type($value);
    }

    private function redacted(string $key): bool
    {
        $needles = config('sunny.log.redact', []);

        if (! is_array($needles)) {
            return false;
        }

        $key = strtolower($key);

        foreach ($needles as $needle) {
            if (is_string($needle) && strtolower($needle) === $key) {
                return true;
            }
        }

        return false;
    }
}
