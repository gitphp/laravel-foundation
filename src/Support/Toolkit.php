<?php

declare(strict_types=1);

namespace Sunny\Foundation\Support;

use Throwable;

final class Toolkit
{
    private function __construct()
    {
    }

    public static function uuid(): string
    {
        $bytes = random_bytes(16);
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

    public static function blank(mixed $value): bool
    {
        return blank($value);
    }

    public static function filled(mixed $value): bool
    {
        return filled($value);
    }
}
