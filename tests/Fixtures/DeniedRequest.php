<?php

declare(strict_types=1);

namespace Sunny\Foundation\Tests\Fixtures;

use Sunny\Foundation\Http\Requests\FormRequest;

class DeniedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
