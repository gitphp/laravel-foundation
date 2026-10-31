<?php

declare(strict_types=1);

/**
 * 验证规则测试
 *
 * @package     Sunny\Foundation\Tests\Validation
 * @license     MIT License (MIT)
 * @author      Sunny Yang <githup@163.com> QQ4873473
 * @copyright   Copyright (c) 2026 Sunny Yang. All rights reserved.
 * @link        http://www.budff.com
 */

namespace Sunny\Foundation\Tests\Validation;

use Sunny\Foundation\Tests\TestCase;
use Sunny\Foundation\Validation\Rules;

/**
 * 验证规则测试
 */
class RulesTest extends TestCase
{
    /**
     * 手机号、小数位数和身份证校验位应符合规则。
     */
    public function test_mobile_and_decimal_rules(): void
    {
        $this->assertTrue(validator(['mobile' => '13800138000'], ['mobile' => Rules::mobile()])->passes());
        $this->assertTrue(validator(['mobile' => '12345'], ['mobile' => Rules::mobile()])->fails());
        $this->assertTrue(validator(['amount' => '12.34'], ['amount' => Rules::decimal()])->passes());
        $this->assertTrue(validator(['amount' => '12.345'], ['amount' => Rules::decimal()])->fails());
        $this->assertTrue(validator(['amount' => '-1'], ['amount' => Rules::decimal()])->fails());
        $this->assertTrue(validator(
            ['id_card' => '110101199003078515'],
            ['id_card' => Rules::chineseIdCard()],
        )->passes());
    }
}

