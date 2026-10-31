<?php

declare(strict_types=1);

namespace Sunny\Foundation\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Sunny\Foundation\Enums\DateFormat;

final class DateTime
{
    private function __construct()
    {
    }

    public static function now(?string $timezone = null): CarbonImmutable
    {
        return CarbonImmutable::now($timezone ?? self::timezone());
    }

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

    public static function format(CarbonInterface|DateTimeInterface|string|int $value, DateFormat|string $format = DateFormat::DateTime, ?string $timezone = null): string
    {
        $pattern = $format instanceof DateFormat ? $format->value : $format;

        return self::parse($value, $timezone)->format($pattern);
    }

    public static function startOfDay(CarbonInterface|DateTimeInterface|string|int $value, ?string $timezone = null): CarbonImmutable
    {
        return self::parse($value, $timezone)->startOfDay();
    }

    public static function endOfDay(CarbonInterface|DateTimeInterface|string|int $value, ?string $timezone = null): CarbonImmutable
    {
        return self::parse($value, $timezone)->endOfDay();
    }

    public static function startOfMonth(CarbonInterface|DateTimeInterface|string|int $value, ?string $timezone = null): CarbonImmutable
    {
        return self::parse($value, $timezone)->startOfMonth();
    }

    public static function endOfMonth(CarbonInterface|DateTimeInterface|string|int $value, ?string $timezone = null): CarbonImmutable
    {
        return self::parse($value, $timezone)->endOfMonth();
    }

    public static function isWeekend(CarbonInterface|DateTimeInterface|string|int $value, ?string $timezone = null): bool
    {
        return self::parse($value, $timezone)->isWeekend();
    }

    public static function isWeekday(CarbonInterface|DateTimeInterface|string|int $value, ?string $timezone = null): bool
    {
        return self::parse($value, $timezone)->isWeekday();
    }

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

    public static function diffInDays(
        CarbonInterface|DateTimeInterface|string|int $from,
        CarbonInterface|DateTimeInterface|string|int $to,
        ?string $timezone = null,
    ): int {
        return (int) self::parse($from, $timezone)->startOfDay()->diffInDays(
            self::parse($to, $timezone)->startOfDay(),
            false,
        );
    }

    public static function human(CarbonInterface|DateTimeInterface|string|int $value, ?string $timezone = null): string
    {
        return self::parse($value, $timezone)->diffForHumans();
    }

    public static function convert(CarbonInterface|DateTimeInterface|string|int $value, string $to, ?string $from = null): CarbonImmutable
    {
        return self::parse($value, $from)->setTimezone($to);
    }

    public static function timestamp(CarbonInterface|DateTimeInterface|string|int $value, ?string $timezone = null): int
    {
        return self::parse($value, $timezone)->getTimestamp();
    }

    /**
     * @return array<int, string>
     */
    public static function eachDay(CarbonInterface|DateTimeInterface|string|int $start, CarbonInterface|DateTimeInterface|string|int $end, ?string $timezone = null): array
    {
        $from = self::parse($start, $timezone)->startOfDay();
        $to = self::parse($end, $timezone)->startOfDay();

        if ($to->lessThan($from)) {
            throw new \InvalidArgumentException('The end date must be on or after the start date.');
        }

        $days = [];

        for ($cursor = $from; $cursor->lessThanOrEqualTo($to); $cursor = $cursor->addDay()) {
            $days[] = $cursor->toDateString();
        }

        return $days;
    }

    private static function timezone(): string
    {
        $timezone = config('app.timezone');

        return is_string($timezone) && $timezone !== '' ? $timezone : 'UTC';
    }
}
