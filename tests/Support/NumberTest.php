<?php

declare(strict_types=1);

/**
 * 数字处理测试
 *
 * @package     Sunny\Foundation\Tests\Support
 * @license     MIT License (MIT)
 * @author      Sunny Yang <githup@163.com> QQ4873473
 * @copyright   Copyright (c) 2026 Sunny Yang. All rights reserved.
 * @link        http://www.budff.com
 */

namespace Sunny\Foundation\Tests\Support;

use InvalidArgumentException;
use Sunny\Foundation\Support\Number;
use Sunny\Foundation\Tests\TestCase;

/**
 * 数字处理测试
 */
class NumberTest extends TestCase
{
    /**
     * 分和元的换算以及展示格式应正确。
     */
    public function test_it_converts_fen_and_yuan(): void
    {
        $this->assertSame('12.34', Number::yuan(1234));
        $this->assertSame('1234', Number::fen('12.34'));
        $this->assertSame('1,234.50', Number::format('1234.5', 2, '.', ','));
    }

    /**
     * 比较、除法、百分比和区间限制应使用指定精度。
     */
    public function test_it_compares_and_calculates_with_scale(): void
    {
        $this->assertTrue(Number::eq('1.10', '1.1'));
        $this->assertSame('3.33', Number::div(10, 3, 2));
        $this->assertSame('25.00', Number::percent(1, 4, 2));
        $this->assertSame('8.00', Number::clamp('12', '1', '8'));
    }

    /**
     * 除数为零时应抛出异常。
     */
    public function test_it_rejects_division_by_zero(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Number::div(1, 0);
    }
}

