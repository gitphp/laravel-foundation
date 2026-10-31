<?php

declare(strict_types=1);

namespace Sunny\Foundation\Support;

use Illuminate\Support\Str as LaravelStr;

final class Str
{
    private function __construct()
    {
    }

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

    public static function maskMobile(string $mobile): string
    {
        return self::mask($mobile, 3, 4);
    }

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

    public static function maskIdCard(string $idCard): string
    {
        return self::mask($idCard, 6, 4);
    }

    public static function isMobile(string $value): bool
    {
        return preg_match('/^1[3-9]\d{9}$/', $value) === 1;
    }

    public static function isEmail(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function isJson(string $value): bool
    {
        if (trim($value) === '') {
            return false;
        }

        json_decode($value);

        return json_last_error() === JSON_ERROR_NONE;
    }

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

        $weights = [7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2];
        $checks = ['1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2'];
        $sum = 0;

        for ($index = 0; $index < 17; $index++) {
            $sum += (int) $value[$index] * $weights[$index];
        }

        return $value[17] === $checks[$sum % 11];
    }

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

    public static function onlyDigits(string $value): string
    {
        $digits = preg_replace('/\D+/', '', $value);

        return is_string($digits) ? $digits : '';
    }

    public static function between(string $value, string $from, string $to): string
    {
        return LaravelStr::between($value, $from, $to);
    }

    public static function before(string $value, string $search): string
    {
        return LaravelStr::before($value, $search);
    }

    public static function after(string $value, string $search): string
    {
        return LaravelStr::after($value, $search);
    }

    public static function snake(string $value): string
    {
        return LaravelStr::snake($value);
    }

    public static function camel(string $value): string
    {
        return LaravelStr::camel($value);
    }

    public static function studly(string $value): string
    {
        return LaravelStr::studly($value);
    }

    public static function slug(string $value, string $separator = '-'): string
    {
        return LaravelStr::slug($value, $separator);
    }

    public static function lower(string $value): string
    {
        return LaravelStr::lower($value);
    }

    public static function upper(string $value): string
    {
        return LaravelStr::upper($value);
    }

    public static function squish(string $value): string
    {
        return LaravelStr::squish($value);
    }

    public static function contains(string $haystack, string $needle): bool
    {
        return LaravelStr::contains($haystack, $needle);
    }

    public static function startsWith(string $haystack, string $needle): bool
    {
        return LaravelStr::startsWith($haystack, $needle);
    }

    public static function endsWith(string $haystack, string $needle): bool
    {
        return LaravelStr::endsWith($haystack, $needle);
    }
}
