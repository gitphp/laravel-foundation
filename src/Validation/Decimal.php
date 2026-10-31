<?php

declare(strict_types=1);

/**
 * 小数验证规则
 *
 * @package     Sunny\Foundation\Validation
 * @license     MIT License (MIT)
 * @author      Sunny Yang <githup@163.com> QQ4873473
 * @copyright   Copyright (c) 2026 Sunny Yang. All rights reserved.
 * @link        http://www.budff.com
 */

namespace Sunny\Foundation\Validation;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use InvalidArgumentException;
use Sunny\Foundation\Support\Number;

/**
 * 小数位数校验规则，默认不允许负数。
 */
final class Decimal implements ValidationRule
{
    /**
     * @param  int  $scale  允许的最大小数位数
     * @param  bool  $unsigned  为 true 时拒绝负数
     */
    public function __construct(
        private readonly int $scale = 2,
        private readonly bool $unsigned = true,
    ) {
    }

    /**
     * 用 BCMath 归一化后再检查符号和小数位数，避免浮点误差。
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_int($value) && ! is_float($value) && ! is_string($value)) {
            $fail(__('sunny::messages.decimal', ['attribute' => $attribute]));

            return;
        }

        try {
            $numeric = Number::normalize($value);
        } catch (InvalidArgumentException) {
            $fail(__('sunny::messages.decimal', ['attribute' => $attribute]));

            return;
        }

        // 金额类字段默认不能是负数。
        if ($this->unsigned && str_starts_with($numeric, '-')) {
            $fail(__('sunny::messages.decimal', ['attribute' => $attribute]));

            return;
        }

        $decimals = str_contains($numeric, '.') ? strlen((string) strrchr($numeric, '.')) - 1 : 0;

        if ($decimals > $this->scale) {
            $fail(__('sunny::messages.decimal_scale', [
                'attribute' => $attribute,
                'scale' => $this->scale,
            ]));
        }
    }
}
