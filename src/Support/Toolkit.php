<?php

declare(strict_types=1);

/**
 * 通用工具
 *
 * @package     Sunny\Foundation\Support
 * @license     MIT License (MIT)
 * @author      Sunny Yang <githup@163.com> QQ4873473
 * @copyright   Copyright (c) 2026 Sunny Yang. All rights reserved.
 * @link        http://www.budff.com
 */

namespace Sunny\Foundation\Support;

use Throwable;

/**
 * 通用工具
 */
final class Toolkit
{
    /**
     * 工具类只提供静态方法。
     */
    private function __construct()
    {
    }

    /**
     * 生成 UUID 版本 4。
     */
    public static function uuid(): string
    {
        $bytes = random_bytes(16);
        // 第 7 个字节高四位固定为 0100，表示 UUID 版本 4；第 9 个字节固定为 RFC 变体。
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12),
        );
    }

    /**
     * 把字节数格式化为 B、KB、MB 等单位。
     */
    public static function formatBytes(int $bytes, int $precision = 2): string
    {
        if ($bytes < 0) {
            throw new \InvalidArgumentException('Bytes must be zero or greater.');
        }

        if ($precision < 0) {
            throw new \InvalidArgumentException('Precision must be zero or greater.');
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $value = (float) $bytes;
        $index = 0;

        while ($value >= 1024 && $index < count($units) - 1) {
            $value /= 1024;
            $index++;
        }

        if ($index === 0) {
            return $bytes.' '.$units[0];
        }

        return number_format($value, $precision, '.', '').' '.$units[$index];
    }

    /**
     * 失败后重试，次数用尽后抛出最后一次异常。
     */
    public static function retry(int $times, callable $callback, int $sleepMilliseconds = 0): mixed
    {
        if ($times < 1) {
            throw new \InvalidArgumentException('Retry attempts must be at least 1.');
        }

        if ($sleepMilliseconds < 0) {
            throw new \InvalidArgumentException('Sleep must be zero or greater.');
        }

        $exception = null;

        for ($attempt = 1; $attempt <= $times; $attempt++) {
            try {
                return $callback($attempt);
            } catch (Throwable $caught) {
                $exception = $caught;

                if ($attempt >= $times) {
                    break;
                }

                if ($sleepMilliseconds > 0) {
                    usleep($sleepMilliseconds * 1000);
                }
            }
        }

        throw $exception instanceof Throwable
            ? $exception
            : new \RuntimeException('Retry failed.');
    }

    /**
     * 值是否为空。
     */
    public static function blank(mixed $value): bool
    {
        return blank($value);
    }

    /**
     * 值是否已填写。
     */
    public static function filled(mixed $value): bool
    {
        return filled($value);
    }
}

