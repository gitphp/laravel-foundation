<?php

declare(strict_types=1);

namespace Sunny\Foundation\Validation;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Sunny\Foundation\Support\Str;

final class ChineseIdCard implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_scalar($value) || ! Str::isChineseIdCard((string) $value)) {
            $fail(__('sunny::messages.id_card', ['attribute' => $attribute]));
        }
    }
}
