<?php

declare(strict_types=1);

/**
 * 测试用的拒绝授权请求
 *
 * @package     Sunny\Foundation\Tests\Fixtures
 * @license     MIT License (MIT)
 * @author      Sunny Yang <githup@163.com> QQ4873473
 * @copyright   Copyright (c) 2026 Sunny Yang. All rights reserved.
 * @link        http://www.budff.com
 */

namespace Sunny\Foundation\Tests\Fixtures;

use Sunny\Foundation\Http\Requests\FormRequest;

/**
 * 测试用的拒绝授权请求
 */
class DeniedRequest extends FormRequest
{
    /**
     * 始终拒绝，用来验证 403 响应。
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * 授权失败发生在规则校验之前，这里不需要字段规则。
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}

