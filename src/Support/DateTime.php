<?php

declare(strict_types=1);

/**
 * 日期时间处理
 *
 * @package     Sunny\Foundation\Support
 * @license     MIT License (MIT)
 * @author      Sunny Yang <githup@163.com> QQ4873473
 * @copyright   Copyright (c) 2026 Sunny Yang. All rights reserved.
 * @link        http://www.budff.com
 */

namespace Sunny\Foundation\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Sunny\Foundation\Enums\DateFormat;

/**
 * 日期时间处理
 */
final class DateTime
{
    /**
     * 工具类只提供静态方法。
     */
    private function __construct()
    {
    }

    /**
     * 当前时间。未传时区时使用应用时区。
     */
    public static function now(?string $timezone = null): CarbonImmutable
    {
        return CarbonImmutable::now($timezone ?? self::timezone());
    }

    /**
     * 把时间戳、字符串或日期对象解析为不可变时间。
     */
    public static function parse(CarbonInterface|DateTimeInterface|string|int $value, ?string $timezone = null): CarbonImmutable
    {
        $timezone ??= self::timezone();

        if ($value instanceof CarbonImmutable) {
            return $value->timezoneName === $timezone ? $value : $value->setTimezone($timezone);
        }

        if ($value instanceof DateTimeInterface) {
            return CarbonImmutable::instance(\DateTimeImmutable::createFromInterface($value))->setTimezone($timezone);
        }

        if (is_int($value)) {
            return CarbonImmutable::createFromTimestamp($value, $timezone);
        }

        return CarbonImmutable::parse($value, $timezone);
    }

    /**
     * 按枚举或格式串输出时间。
     */
    public static function format(CarbonInterface|DateTimeInterface|string|int $value, DateFormat|string $format = DateFormat::DateTime, ?string $timezone = null): string
    {
        $pattern = $format instanceof DateFormat ? $format->value : $format;

        return self::parse($value, $timezone)->format($pattern);
    }

    /**
     * 当天开始。
     */
    public static function startOfDay(CarbonInterface|DateTimeInterface|string|int $value, ?string $timezone = null): CarbonImmutable
    {
        return self::parse($value, $timezone)->startOfDay();
    }

    /**
     * 当天结束。
     */
    public static function endOfDay(CarbonInterface|DateTimeInterface|string|int $value, ?string $timezone = null): CarbonImmutable
    {
        return self::parse($value, $timezone)->endOfDay();
    }

    /**
     * 当月第一天开始。
     */
    public static function startOfMonth(CarbonInterface|DateTimeInterface|string|int $value, ?string $timezone = null): CarbonImmutable
    {
        return self::parse($value, $timezone)->startOfMonth();
    }

    /**
     * 当月最后一天结束。
     */
    public static function endOfMonth(CarbonInterface|DateTimeInterface|string|int $value, ?string $timezone = null): CarbonImmutable
    {
        return self::parse($value, $timezone)->endOfMonth();
    }

    /**
     * 是否为周末。
     */
    public static function isWeekend(CarbonInterface|DateTimeInterface|string|int $value, ?string $timezone = null): bool
    {
        return self::parse($value, $timezone)->isWeekend();
    }

    /**
     * 是否为工作日。
     */
    public static function isWeekday(CarbonInterface|DateTimeInterface|string|int $value, ?string $timezone = null): bool
    {
        return self::parse($value, $timezone)->isWeekday();
    }

    /**
     * 是否落在闭区间内。
     */
    public static function isBetween(
        CarbonInterface|DateTimeInterface|string|int $value,
        CarbonInterface|DateTimeInterface|string|int $start,
        CarbonInterface|DateTimeInterface|string|int $end,
        ?string $timezone = null,
    ): bool {
        return self::parse($value, $timezone)->betweenIncluded(
            self::parse($start, $timezone),
            self::parse($end, $timezone),
        );
    }

    /**
     * 两个日期相差的整天数，结果带符号。
     */
    public static function diffInDays(
        CarbonInterface|DateTimeInterface|string|int $from,
        CarbonInterface|DateTimeInterface|string|int $to,
        ?string $timezone = null,
    ): int {
        // 第二个参数 false 表示保留正负号：结束日早于开始日时结果为负。
        return (int) self::parse($from, $timezone)->startOfDay()->diffInDays(
            self::parse($to, $timezone)->startOfDay(),
            false,
        );
    }

    /**
     * 相对当前时间的可读描述。
     */
    public static function human(CarbonInterface|DateTimeInterface|string|int $value, ?string $timezone = null): string
    {
        return self::parse($value, $timezone)->diffForHumans();
    }

    /**
     * 把时间从一个时区转换到另一个时区。
     */
    public static function convert(CarbonInterface|DateTimeInterface|string|int $value, string $to, ?string $from = null): CarbonImmutable
    {
        return self::parse($value, $from)->setTimezone($to);
    }

    /**
     * Unix 时间戳。
     */
    public static function timestamp(CarbonInterface|DateTimeInterface|string|int $value, ?string $timezone = null): int
    {
        return self::parse($value, $timezone)->getTimestamp();
    }

    /**
     * 列出闭区间内的每一天。结束日期早于开始日期时抛出异常。
     *
     * @return array<int, string>
     */
    public static function eachDay(CarbonInterface|DateTimeInterface|string|int $start, CarbonInterface|DateTimeInterface|string|int $end, ?string $timezone = null): array
    {
        $from = self::parse($start, $timezone)->startOfDay();
        $to = self::parse($end, $timezone)->startOfDay();

        // 结束日早于开始日时直接拒绝，不用空数组把传参错误藏起来。
        if ($to->lessThan($from)) {
            throw new \InvalidArgumentException('The end date must be on or after the start date.');
        }

        $days = [];

        for ($cursor = $from; $cursor->lessThanOrEqualTo($to); $cursor = $cursor->addDay()) {
            $days[] = $cursor->toDateString();
        }

        return $days;
    }

    /**
     * 应用时区，未配置时使用 UTC。
     */
    private static function timezone(): string
    {
        $timezone = config('app.timezone');

        return is_string($timezone) && $timezone !== '' ? $timezone : 'UTC';
    }
}

