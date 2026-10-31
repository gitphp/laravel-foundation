<?php

declare(strict_types=1);

/**
 * JSON 表单请求基类
 *
 * 验证失败返回 422，授权失败返回 403，响应体与 ApiResponse 一致。
 * 需要权限控制时重写 authorize()。
 *
 * @package     Sunny\Foundation\Http\Requests
 * @license     MIT License (MIT)
 * @author      Sunny Yang <githup@163.com> QQ4873473
 * @copyright   Copyright (c) 2026 Sunny Yang. All rights reserved.
 * @link        http://www.budff.com
 */

namespace Sunny\Foundation\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest as BaseFormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Sunny\Foundation\Enums\ResponseCode;
use Sunny\Foundation\Http\Responses\ApiResponse;

/**
 * 表单请求基类。验证或授权失败时返回统一的 JSON。
 */
abstract class FormRequest extends BaseFormRequest
{
    /**
     * 默认放行。具体请求按业务重写。
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 把验证错误转成统一的 JSON，而不是 Laravel 默认的错误页。
     */
    protected function failedValidation(Validator $validator): void
    {
        $message = $validator->errors()->first();

        throw new HttpResponseException(ApiResponse::error(
            message: is_string($message) && $message !== '' ? $message : ResponseCode::ValidationFailed->message(),
            code: ResponseCode::ValidationFailed,
            errors: $validator->errors()->toArray(),
        ));
    }

    /**
     * authorize() 返回 false 时返回 403。
     */
    protected function failedAuthorization(): void
    {
        throw new HttpResponseException(ApiResponse::error(
            message: ResponseCode::Forbidden->message(),
            code: ResponseCode::Forbidden,
        ));
    }
}
