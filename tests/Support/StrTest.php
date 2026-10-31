<?php

declare(strict_types=1);

/**
 * 字符串处理测试
 *
 * @package     Sunny\Foundation\Tests\Support
 * @license     MIT License (MIT)
 * @author      Sunny Yang <githup@163.com> QQ4873473
 * @copyright   Copyright (c) 2026 Sunny Yang. All rights reserved.
 * @link        http://www.budff.com
 */

namespace Sunny\Foundation\Tests\Support;

use Sunny\Foundation\Support\Str;
use Sunny\Foundation\Tests\TestCase;

/**
 * 字符串处理测试
 */
class StrTest extends TestCase
{
    /**
     * 手机号、邮箱和身份证应按约定脱敏。
     */
    public function test_it_masks_phone_email_and_id_card(): void
    {
        $this->assertSame('138****8000', Str::maskMobile('13800138000'));
        $this->assertSame('z*******@example.com', Str::maskEmail('zhangsan@example.com'));
        $this->assertSame('110101********8515', Str::maskIdCard('110101199003078515'));
    }

    /**
     * 手机号、JSON 和身份证校验应区分合法与非法值。
     */
    public function test_it_checks_mobile_json_and_chinese_id_card(): void
    {
        $this->assertTrue(Str::isMobile('13800138000'));
        $this->assertFalse(Str::isMobile('12345'));
        $this->assertTrue(Str::isJson('{"ok":true}'));
        $this->assertFalse(Str::isJson(''));
        $this->assertTrue(Str::isChineseIdCard('110101199003078515'));
        $this->assertFalse(Str::isChineseIdCard('110101199003078516'));
    }

    /**
     * 截断、取中间文本和提取数字应得到预期结果。
     */
    public function test_it_limits_and_extracts_digits(): void
    {
        $this->assertSame('晴天...', Str::limit('晴天白云飘过来', 5));
        $this->assertSame('13800138000', Str::onlyDigits('手机 138-0013-8000'));
        $this->assertSame('world', Str::between('hello world!', 'hello ', '!'));
        $this->assertSame(16, strlen(Str::random(16)));
    }
}

