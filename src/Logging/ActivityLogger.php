<?php

declare(strict_types=1);

/**
 * 活动日志
 *
 * @package     Sunny\Foundation\Logging
 * @license     MIT License (MIT)
 * @author      Sunny Yang <githup@163.com> QQ4873473
 * @copyright   Copyright (c) 2026 Sunny Yang. All rights reserved.
 * @link        http://www.budff.com
 */

namespace Sunny\Foundation\Logging;

/**
 * 活动日志
 */
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

    /**
     * 写入指定级别的日志，并打码敏感字段。
     */
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

    /**
     * 记录调试日志。
     */
    public function debug(string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    /**
     * 记录普通信息。
     */
    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    /**
     * 记录需要关注但不算错误的信息。
     */
    public function notice(string $message, array $context = []): void
    {
        $this->log('notice', $message, $context);
    }

    /**
     * 记录警告。
     */
    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    /**
     * 记录错误。
     */
    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    /**
     * 记录严重错误。
     */
    public function critical(string $message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    /**
     * 记录异常的类名、消息和位置。默认不写堆栈。
     */
    public function exception(\Throwable $exception, string $message = '', array $context = []): void
    {
        $details = [
            'class' => $exception::class,
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ];

        // 默认不写堆栈，避免把参数和路径带进日志。只有显式打开时才记录。
        if (config('sunny.log.trace') === true) {
            $details['trace'] = $exception->getTraceAsString();
        }

        $context['exception'] = $details;
        $this->error($message !== '' ? $message : $exception->getMessage(), $context);
    }

    /**
     * 递归清理上下文，并替换敏感键。
     *
     * @param  array<mixed>  $context
     * @return array<mixed>
     */
    private function sanitize(array $context, int $depth = 0): array
    {
        $clean = [];

        foreach ($context as $key => $value) {
            // 密码、令牌等键整段替换，嵌套数组里的同名键也会处理。
            if (is_string($key) && $this->redacted($key)) {
                $clean[$key] = '[redacted]';

                continue;
            }

            $clean[$key] = $this->sanitizeValue($value, $depth);
        }

        return $clean;
    }

    /**
     * 把对象转成类名，并截断过长的字符串。
     */
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

    /**
     * 判断字段名是否在打码名单里，比较时忽略大小写。
     */
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

