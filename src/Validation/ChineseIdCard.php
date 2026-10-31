<?php

declare(strict_types=1);

/**
 * 身份证号验证规则
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
use Sunny\Foundation\Support\Str;

/**
 * 18 位居民身份证校验规则。
 */
final class ChineseIdCard implements ValidationRule
{
    /**
     * 校验 18 位身份证的日期和校验位。
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_scalar($value) || ! Str::isChineseIdCard((string) $value)) {
            $fail(__('sunny::messages.id_card', ['attribute' => $attribute]));
        }
    }
}
