<?php

declare(strict_types=1);

/**
 * 数字与金额处理
 *
 * @package     Sunny\Foundation\Support
 * @license     MIT License (MIT)
 * @author      Sunny Yang <githup@163.com> QQ4873473
 * @copyright   Copyright (c) 2026 Sunny Yang. All rights reserved.
 * @link        http://www.budff.com
 */

namespace Sunny\Foundation\Support;

/**
 * 数字与金额处理
 */
final class Number
{
    /**
     * 工具类只提供静态方法。
     */
    private function __construct()
    {
    }

    /**
     * 精确相加。
     */
    public static function add(int|float|string $left, int|float|string $right, int $scale = 2): string
    {
        self::assertScale($scale);

        return self::round(bcadd(self::numeric($left), self::numeric($right), $scale + 2), $scale);
    }

    /**
     * 精确相减。
     */
    public static function sub(int|float|string $left, int|float|string $right, int $scale = 2): string
    {
        self::assertScale($scale);

        return self::round(bcsub(self::numeric($left), self::numeric($right), $scale + 2), $scale);
    }

    /**
     * 精确相乘。
     */
    public static function mul(int|float|string $left, int|float|string $right, int $scale = 2): string
    {
        self::assertScale($scale);

        return self::round(bcmul(self::numeric($left), self::numeric($right), $scale + 2), $scale);
    }

    /**
     * 精确相除。除数为 0 时抛出异常。
     */
    public static function div(int|float|string $left, int|float|string $right, int $scale = 2): string
    {
        self::assertScale($scale);
        $divisor = self::numeric($right);

        // BCMath 除以 0 会得到警告或错误结果，这里先拦住。
        if (bccomp($divisor, '0', 8) === 0) {
            throw new \InvalidArgumentException('Division by zero.');
        }

        return self::round(bcdiv(self::numeric($left), $divisor, $scale + 2), $scale);
    }

    /**
     * 比较两个数。返回 -1、0 或 1。
     */
    public static function compare(int|float|string $left, int|float|string $right, int $scale = 8): int
    {
        self::assertScale($scale);

        return bccomp(self::numeric($left), self::numeric($right), $scale);
    }

    /**
     * 两个数在指定精度下是否相等。
     */
    public static function eq(int|float|string $left, int|float|string $right, int $scale = 8): bool
    {
        return self::compare($left, $right, $scale) === 0;
    }

    /**
     * 左值是否大于右值。
     */
    public static function gt(int|float|string $left, int|float|string $right, int $scale = 8): bool
    {
        return self::compare($left, $right, $scale) === 1;
    }

    /**
     * 左值是否大于或等于右值。
     */
    public static function gte(int|float|string $left, int|float|string $right, int $scale = 8): bool
    {
        return self::compare($left, $right, $scale) >= 0;
    }

    /**
     * 左值是否小于右值。
     */
    public static function lt(int|float|string $left, int|float|string $right, int $scale = 8): bool
    {
        return self::compare($left, $right, $scale) === -1;
    }

    /**
     * 左值是否小于或等于右值。
     */
    public static function lte(int|float|string $left, int|float|string $right, int $scale = 8): bool
    {
        return self::compare($left, $right, $scale) <= 0;
    }

    /**
     * 是否为零。
     */
    public static function isZero(int|float|string $value): bool
    {
        return self::eq($value, '0');
    }

    /**
     * 把数值限制在最小值和最大值之间。
     */
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

    /**
     * 计算百分比，结果不带百分号。
     */
    public static function percent(int|float|string $part, int|float|string $whole, int $scale = 2): string
    {
        return self::mul(self::div($part, $whole, $scale + 2), '100', $scale);
    }

    /**
     * 分转元。
     */
    public static function yuan(int|float|string $fen, int $scale = 2): string
    {
        return self::div($fen, '100', $scale);
    }

    /**
     * 元转分，四舍五入到整数。
     */
    public static function fen(int|float|string $yuan): string
    {
        return self::mul($yuan, '100', 0);
    }

    /**
     * 按指定小数位和千分位格式化，仅用于展示。
     */
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

    /**
     * 转成去掉多余零的十进制字符串。
     */
    public static function normalize(int|float|string $value): string
    {
        return self::canonicalize(self::numeric($value));
    }

    /**
     * 按远离零的方式四舍五入，不使用浮点数。
     */
    public static function round(int|float|string $value, int $scale = 2): string
    {
        self::assertScale($scale);
        $numeric = self::numeric($value);
        // 在下一位加或减 0.5 后再截断。负数走减法，得到远离零的四舍五入，全程不使用浮点数。
        $increment = $scale === 0 ? '0.5' : '0.'.str_repeat('0', $scale).'5';

        if (str_starts_with($numeric, '-')) {
            $rounded = bcsub($numeric, $increment, $scale);
        } else {
            $rounded = bcadd($numeric, $increment, $scale);
        }

        return self::pad($rounded, $scale);
    }

    /**
     * 把整数、有限浮点数或数字字符串转成 BCMath 可接受的形式。
     */
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

    /**
     * 去掉正号、前导零和小数末尾的零。
     */
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

    /**
     * 补齐小数位，并把负零收成 0。
     */
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

    /**
     * 小数位数必须在 0 到 18 之间。
     */
    private static function assertScale(int $scale): void
    {
        if ($scale < 0 || $scale > 18) {
            throw new \InvalidArgumentException('Scale must be between 0 and 18.');
        }
    }

    /**
     * 确认已安装 bcmath 扩展。
     */
    private static function assertBcmath(): void
    {
        if (! function_exists('bcadd')) {
            throw new \RuntimeException('The bcmath extension is required.');
        }
    }
}

