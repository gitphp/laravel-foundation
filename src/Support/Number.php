<?php

declare(strict_types=1);

namespace Sunny\Foundation\Support;

final class Number
{
    private function __construct()
    {
    }

    public static function add(int|float|string $left, int|float|string $right, int $scale = 2): string
    {
        self::assertScale($scale);

        return self::round(bcadd(self::numeric($left), self::numeric($right), $scale + 2), $scale);
    }

    public static function sub(int|float|string $left, int|float|string $right, int $scale = 2): string
    {
        self::assertScale($scale);

        return self::round(bcsub(self::numeric($left), self::numeric($right), $scale + 2), $scale);
    }

    public static function mul(int|float|string $left, int|float|string $right, int $scale = 2): string
    {
        self::assertScale($scale);

        return self::round(bcmul(self::numeric($left), self::numeric($right), $scale + 2), $scale);
    }

    public static function div(int|float|string $left, int|float|string $right, int $scale = 2): string
    {
        self::assertScale($scale);
        $divisor = self::numeric($right);

        if (bccomp($divisor, '0', 8) === 0) {
            throw new \InvalidArgumentException('Division by zero.');
        }

        return self::round(bcdiv(self::numeric($left), $divisor, $scale + 2), $scale);
    }

    public static function compare(int|float|string $left, int|float|string $right, int $scale = 8): int
    {
        self::assertScale($scale);

        return bccomp(self::numeric($left), self::numeric($right), $scale);
    }

    public static function eq(int|float|string $left, int|float|string $right, int $scale = 8): bool
    {
        return self::compare($left, $right, $scale) === 0;
    }

    public static function gt(int|float|string $left, int|float|string $right, int $scale = 8): bool
    {
        return self::compare($left, $right, $scale) === 1;
    }

    public static function gte(int|float|string $left, int|float|string $right, int $scale = 8): bool
    {
        return self::compare($left, $right, $scale) >= 0;
    }

    public static function lt(int|float|string $left, int|float|string $right, int $scale = 8): bool
    {
        return self::compare($left, $right, $scale) === -1;
    }

    public static function lte(int|float|string $left, int|float|string $right, int $scale = 8): bool
    {
        return self::compare($left, $right, $scale) <= 0;
    }

    public static function isZero(int|float|string $value): bool
    {
        return self::eq($value, '0');
    }

    public static function clamp(int|float|string $value, int|float|string $min, int|float|string $max, int $scale = 2): string
    {
        if (self::compare($min, $max, $scale) === 1) {
            throw new \InvalidArgumentException('Minimum must be less than or equal to maximum.');
        }

        if (self::compare($value, $min, $scale) === -1) {
            return self::round($min, $scale);
        }

        if (self::compare($value, $max, $scale) === 1) {
            return self::round($max, $scale);
        }

        return self::round($value, $scale);
    }

    public static function percent(int|float|string $part, int|float|string $whole, int $scale = 2): string
    {
        return self::mul(self::div($part, $whole, $scale + 2), '100', $scale);
    }

    public static function yuan(int|float|string $fen, int $scale = 2): string
    {
        return self::div($fen, '100', $scale);
    }

    public static function fen(int|float|string $yuan): string
    {
        return self::mul($yuan, '100', 0);
    }

    public static function format(int|float|string $value, int $scale = 2, string $decimal = '.', string $thousands = ''): string
    {
        $normalized = self::round($value, $scale);
        $negative = str_starts_with($normalized, '-');
        $normalized = ltrim($normalized, '-');
        [$whole, $fraction] = array_pad(explode('.', $normalized, 2), 2, '');

        if ($thousands !== '') {
            $replaced = preg_replace('/\B(?=(\d{3})+(?!\d))/', $thousands, $whole);
            $whole = is_string($replaced) ? $replaced : $whole;
        }

        $result = $scale > 0 ? $whole.$decimal.str_pad($fraction, $scale, '0') : $whole;

        return $negative ? '-'.$result : $result;
    }

    public static function normalize(int|float|string $value): string
    {
        return self::canonicalize(self::numeric($value));
    }

    public static function round(int|float|string $value, int $scale = 2): string
    {
        self::assertScale($scale);
        $numeric = self::numeric($value);
        $increment = $scale === 0 ? '0.5' : '0.'.str_repeat('0', $scale).'5';

        if (str_starts_with($numeric, '-')) {
            $rounded = bcsub($numeric, $increment, $scale);
        } else {
            $rounded = bcadd($numeric, $increment, $scale);
        }

        return self::pad($rounded, $scale);
    }

    public static function numeric(int|float|string $value): string
    {
        self::assertBcmath();

        if (is_int($value)) {
            return (string) $value;
        }

        if (is_float($value)) {
            if (! is_finite($value)) {
                throw new \InvalidArgumentException('Number must be finite.');
            }

            return self::canonicalize(number_format($value, 8, '.', ''));
        }

        $value = trim($value);

        if (preg_match('/^[+-]?\d+(\.\d+)?$/', $value) !== 1) {
            throw new \InvalidArgumentException('Invalid numeric value.');
        }

        return self::canonicalize($value);
    }

    private static function canonicalize(string $value): string
    {
        $negative = str_starts_with($value, '-');
        $value = ltrim($value, '+-');

        if (! str_contains($value, '.')) {
            $value = ltrim($value, '0');
            $value = $value === '' ? '0' : $value;

            return $negative && $value !== '0' ? '-'.$value : $value;
        }

        [$whole, $fraction] = explode('.', $value, 2);
        $whole = ltrim($whole, '0');
        $whole = $whole === '' ? '0' : $whole;
        $fraction = rtrim($fraction, '0');
        $result = $fraction === '' ? $whole : $whole.'.'.$fraction;

        return $negative && $result !== '0' ? '-'.$result : $result;
    }

    private static function pad(string $value, int $scale): string
    {
        $negative = str_starts_with($value, '-');
        $value = ltrim($value, '-');

        if (! str_contains($value, '.')) {
            $value .= $scale > 0 ? '.'.str_repeat('0', $scale) : '';
        } else {
            [$whole, $fraction] = explode('.', $value, 2);
            $fraction = str_pad(substr($fraction, 0, $scale), $scale, '0');
            $value = $scale > 0 ? $whole.'.'.$fraction : $whole;
        }

        if (preg_match('/^0+(?:\.0+)?$/', $value) === 1) {
            $negative = false;
        }

        return $negative ? '-'.$value : $value;
    }

    private static function assertScale(int $scale): void
    {
        if ($scale < 0 || $scale > 18) {
            throw new \InvalidArgumentException('Scale must be between 0 and 18.');
        }
    }

    private static function assertBcmath(): void
    {
        if (! function_exists('bcadd')) {
            throw new \RuntimeException('The bcmath extension is required.');
        }
    }
}
