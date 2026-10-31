<?php

declare(strict_types=1);

namespace Sunny\Foundation\Tests\Validation;

use Sunny\Foundation\Tests\TestCase;
use Sunny\Foundation\Validation\Rules;

class RulesTest extends TestCase
{
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
