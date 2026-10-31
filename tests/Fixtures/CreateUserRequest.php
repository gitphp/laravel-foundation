<?php

declare(strict_types=1);

namespace Sunny\Foundation\Tests\Fixtures;

use Sunny\Foundation\Http\Requests\FormRequest;
use Sunny\Foundation\Validation\Rules;

class CreateUserRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50'],
            'mobile' => ['required', ...Rules::mobile()],
        ];
    }
}
