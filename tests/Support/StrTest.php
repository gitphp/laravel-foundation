<?php

declare(strict_types=1);

namespace Sunny\Foundation\Tests\Support;

use Sunny\Foundation\Support\Str;
use Sunny\Foundation\Tests\TestCase;

class StrTest extends TestCase
{
    public function test_it_masks_phone_email_and_id_card(): void
    {
        $this->assertSame('138****8000', Str::maskMobile('13800138000'));
        $this->assertSame('z*******@example.com', Str::maskEmail('zhangsan@example.com'));
        $this->assertSame('110101********8515', Str::maskIdCard('110101199003078515'));
    }

    public function test_it_checks_mobile_json_and_chinese_id_card(): void
    {
        $this->assertTrue(Str::isMobile('13800138000'));
        $this->assertFalse(Str::isMobile('12345'));
        $this->assertTrue(Str::isJson('{"ok":true}'));
        $this->assertFalse(Str::isJson(''));
        $this->assertTrue(Str::isChineseIdCard('110101199003078515'));
        $this->assertFalse(Str::isChineseIdCard('110101199003078516'));
    }

    public function test_it_limits_and_extracts_digits(): void
    {
        $this->assertSame('晴天...', Str::limit('晴天白云飘过来', 5));
        $this->assertSame('13800138000', Str::onlyDigits('手机 138-0013-8000'));
        $this->assertSame('world', Str::between('hello world!', 'hello ', '!'));
        $this->assertSame(16, strlen(Str::random(16)));
    }
}
