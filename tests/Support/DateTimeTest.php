<?php

declare(strict_types=1);

namespace Sunny\Foundation\Tests\Support;

use InvalidArgumentException;
use Sunny\Foundation\Enums\DateFormat;
use Sunny\Foundation\Support\DateTime;
use Sunny\Foundation\Tests\TestCase;

class DateTimeTest extends TestCase
{
    public function test_it_formats_weekends_ranges_and_timezones(): void
    {
        $this->assertSame('2024-01-06', DateTime::format('2024-01-06 15:00:00', DateFormat::Date));
        $this->assertTrue(DateTime::isWeekend('2024-01-06'));
        $this->assertTrue(DateTime::isWeekday('2024-01-05'));
        $this->assertTrue(DateTime::isBetween('2024-01-06', '2024-01-05', '2024-01-07'));
        $this->assertSame(2, DateTime::diffInDays('2024-01-01', '2024-01-03'));
        $this->assertSame(
            ['2024-01-05', '2024-01-06', '2024-01-07'],
            DateTime::eachDay('2024-01-05', '2024-01-07'),
        );
        $this->assertSame(
            '2024-01-01 08:00:00',
            DateTime::convert('2024-01-01 00:00:00', 'Asia/Shanghai', 'UTC')->format('Y-m-d H:i:s'),
        );
    }

    public function test_it_rejects_an_inverted_range(): void
    {
        $this->expectException(InvalidArgumentException::class);

        DateTime::eachDay('2024-01-10', '2024-01-01');
    }
}
