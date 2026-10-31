<?php

declare(strict_types=1);

/**
 * 字符串处理
 *
 * @package     Sunny\Foundation\Support
 * @license     MIT License (MIT)
 * @author      Sunny Yang <githup@163.com> QQ4873473
 * @copyright   Copyright (c) 2026 Sunny Yang. All rights reserved.
 * @link        http://www.budff.com
 */

namespace Sunny\Foundation\Support;

use Illuminate\Support\Str as LaravelStr;

/**
 * 字符串处理
 */
final class Str
{
    /**
     * 工具类只提供静态方法。
     */
    private function __construct()
    {
    }

    /**
     * 保留首尾若干字符，中间用掩码替换。
     */
    public static function mask(string $value, int $visibleStart = 3, int $visibleEnd = 4, string $mask = '*'): string
    {
        if ($visibleStart < 0 || $visibleEnd < 0 || $mask === '') {
            throw new \InvalidArgumentException('Mask arguments are invalid.');
        }

        $length = mb_strlen($value);

        if ($length === 0) {
            return '';
        }

        if ($length <= $visibleStart + $visibleEnd) {
            return str_repeat($mask, $length);
        }

        $hidden = $length - $visibleStart - $visibleEnd;

        return mb_substr($value, 0, $visibleStart)
            .str_repeat($mask, $hidden)
            .mb_substr($value, $length - $visibleEnd);
    }

    /**
     * 手机号保留前 3 位和后 4 位。
     */
    public static function maskMobile(string $mobile): string
    {
        return self::mask($mobile, 3, 4);
    }

    /**
     * 邮箱只保留本地部分的首字符。
     */
    public static function maskEmail(string $email): string
    {
        if (! str_contains($email, '@')) {
            return self::mask($email, 1, 0);
        }

        [$local, $domain] = explode('@', $email, 2);
        $length = mb_strlen($local);

        if ($length <= 1) {
            $masked = str_repeat('*', max(1, $length));
        } else {
            $masked = mb_substr($local, 0, 1).str_repeat('*', $length - 1);
        }

        return $masked.'@'.$domain;
    }

    /**
     * 身份证号保留前 6 位和后 4 位。
     */
    public static function maskIdCard(string $idCard): string
    {
        return self::mask($idCard, 6, 4);
    }

    /**
     * 是否为 1 开头的 11 位大陆手机号。
     */
    public static function isMobile(string $value): bool
    {
        return preg_match('/^1[3-9]\d{9}$/', $value) === 1;
    }

    /**
     * 是否为合法邮箱。
     */
    public static function isEmail(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * 字符串能否被解码为 JSON。空字符串不算。
     */
    public static function isJson(string $value): bool
    {
        if (trim($value) === '') {
            return false;
        }

        json_decode($value);

        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * 校验 18 位身份证的出生日期和校验位。
     */
    public static function isChineseIdCard(string $value): bool
    {
        $value = strtoupper($value);

        if (preg_match('/^\d{17}[\dX]$/', $value) !== 1) {
            return false;
        }

        $birthday = substr($value, 6, 8);
        $date = \DateTimeImmutable::createFromFormat('!Ymd', $birthday);

        if ($date === false || $date->format('Ymd') !== $birthday) {
            return false;
        }

        // GB 11643 加权因子。前 17 位加权求和后对 11 取余，对照表依次是 1、0、X、9 到 2。
        $weights = [7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2];
        $checks = ['1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2'];
        $sum = 0;

        for ($index = 0; $index < 17; $index++) {
            $sum += (int) $value[$index] * $weights[$index];
        }

        return $value[17] === $checks[$sum % 11];
    }

    /**
     * 用随机数生成指定长度的字符串。
     */
    public static function random(int $length = 16, string $alphabet = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'): string
    {
        if ($length < 1) {
            throw new \InvalidArgumentException('Length must be at least 1.');
        }

        if ($alphabet === '') {
            throw new \InvalidArgumentException('Alphabet must not be empty.');
        }

        $characters = mb_str_split($alphabet);
        $last = count($characters) - 1;
        $result = '';

        for ($index = 0; $index < $length; $index++) {
            $result .= $characters[random_int(0, $last)];
        }

        return $result;
    }

    /**
     * 按字符数截断，超长时追加结尾符。
     */
    public static function limit(string $value, int $limit = 100, string $end = '...'): string
    {
        if ($limit < 1) {
            throw new \InvalidArgumentException('Limit must be at least 1.');
        }

        if (mb_strlen($value) <= $limit) {
            return $value;
        }

        $endLength = mb_strlen($end);

        if ($limit <= $endLength) {
            return mb_substr($end, 0, $limit);
        }

        return mb_substr($value, 0, $limit - $endLength).$end;
    }

    /**
     * 只保留数字。
     */
    public static function onlyDigits(string $value): string
    {
        $digits = preg_replace('/\D+/', '', $value);

        return is_string($digits) ? $digits : '';
    }

    /**
     * 取两个标记之间的文本。
     */
    public static function between(string $value, string $from, string $to): string
    {
        return LaravelStr::between($value, $from, $to);
    }

    /**
     * 取标记之前的文本。
     */
    public static function before(string $value, string $search): string
    {
        return LaravelStr::before($value, $search);
    }

    /**
     * 取标记之后的文本。
     */
    public static function after(string $value, string $search): string
    {
        return LaravelStr::after($value, $search);
    }

    /**
     * 转成下划线命名。
     */
    public static function snake(string $value): string
    {
        return LaravelStr::snake($value);
    }

    /**
     * 转成小驼峰。
     */
    public static function camel(string $value): string
    {
        return LaravelStr::camel($value);
    }

    /**
     * 转成大驼峰。
     */
    public static function studly(string $value): string
    {
        return LaravelStr::studly($value);
    }

    /**
     * 转成 URL 友好的短横线形式。
     */
    public static function slug(string $value, string $separator = '-'): string
    {
        return LaravelStr::slug($value, $separator);
    }

    /**
     * 转成小写。
     */
    public static function lower(string $value): string
    {
        return LaravelStr::lower($value);
    }

    /**
     * 转成大写。
     */
    public static function upper(string $value): string
    {
        return LaravelStr::upper($value);
    }

    /**
     * 把连续空白压成一个空格。
     */
    public static function squish(string $value): string
    {
        return LaravelStr::squish($value);
    }

    /**
     * 是否包含指定文本。
     */
    public static function contains(string $haystack, string $needle): bool
    {
        return LaravelStr::contains($haystack, $needle);
    }

    /**
     * 是否以指定文本开头。
     */
    public static function startsWith(string $haystack, string $needle): bool
    {
        return LaravelStr::startsWith($haystack, $needle);
    }

    /**
     * 是否以指定文本结尾。
     */
    public static function endsWith(string $haystack, string $needle): bool
    {
        return LaravelStr::endsWith($haystack, $needle);
    }
}

