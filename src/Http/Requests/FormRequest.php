<?php

declare(strict_types=1);

namespace Sunny\Foundation\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest as BaseFormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Sunny\Foundation\Enums\ResponseCode;
use Sunny\Foundation\Http\Responses\ApiResponse;

/**
 * Base JSON form request. Override authorize() when the route needs a permission check.
 */
abstract class FormRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function failedValidation(Validator $validator): void
    {
        $message = $validator->errors()->first();

        throw new HttpResponseException(ApiResponse::error(
            message: is_string($message) && $message !== '' ? $message : ResponseCode::ValidationFailed->message(),
            code: ResponseCode::ValidationFailed,
            errors: $validator->errors()->toArray(),
        ));
    }

    protected function failedAuthorization(): void
    {
        throw new HttpResponseException(ApiResponse::error(
            message: ResponseCode::Forbidden->message(),
            code: ResponseCode::Forbidden,
        ));
    }
}
