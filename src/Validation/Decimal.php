<?php

declare(strict_types=1);

namespace Sunny\Foundation\Validation;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use InvalidArgumentException;
use Sunny\Foundation\Support\Number;

final class Decimal implements ValidationRule
{
    public function __construct(
        private readonly int $scale = 2,
        private readonly bool $unsigned = true,
    ) {
    }

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
