<?php

declare(strict_types=1);

/**
 * 测试用的用户表单请求
 *
 * @package     Sunny\Foundation\Tests\Fixtures
 * @license     MIT License (MIT)
 * @author      Sunny Yang <githup@163.com> QQ4873473
 * @copyright   Copyright (c) 2026 Sunny Yang. All rights reserved.
 * @link        http://www.budff.com
 */

namespace Sunny\Foundation\Tests\Fixtures;

use Sunny\Foundation\Http\Requests\FormRequest;
use Sunny\Foundation\Validation\Rules;

/**
 * 测试用的用户表单请求
 */
class CreateUserRequest extends FormRequest
{
    /**
     * 姓名必填，手机号使用本包规则。
     *
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

