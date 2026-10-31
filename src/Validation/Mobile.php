<?php

declare(strict_types=1);

/**
 * 手机号验证规则
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
 * 大陆手机号校验规则。
 */
final class Mobile implements ValidationRule
{
    /**
     * 只接受 1 开头的 11 位大陆手机号。
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_scalar($value) || ! Str::isMobile((string) $value)) {
            $fail(__('sunny::messages.mobile', ['attribute' => $attribute]));
        }
    }
}
