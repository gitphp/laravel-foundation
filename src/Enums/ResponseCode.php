<?php

declare(strict_types=1);

/**
 * API 业务状态码
 *
 * 业务码和 HTTP 状态分开：0 表示成功，40100 这类编码用于响应体里的 code 字段。
 *
 * @package     Sunny\Foundation\Enums
 * @license     MIT License (MIT)
 * @author      Sunny Yang <githup@163.com> QQ4873473
 * @copyright   Copyright (c) 2026 Sunny Yang. All rights reserved.
 * @link        http://www.budff.com
 */

namespace Sunny\Foundation\Enums;

/**
 * 响应体里的业务状态码，和 HTTP 状态分开。
 */
enum ResponseCode: int
{
    /** 成功 */
    case Ok = 0;

    /** 通用失败 */
    case Fail = 1;

    /** 未登录或令牌无效 */
    case Unauthenticated = 40100;

    /** 已登录但没有权限 */
    case Forbidden = 40300;

    /** 资源不存在 */
    case NotFound = 40400;

    /** 表单验证失败 */
    case ValidationFailed = 42200;

    /** 请求过于频繁 */
    case TooManyRequests = 42900;

    /** 服务器错误 */
    case ServerError = 50000;

    /**
     * 映射为 HTTP 状态码。
     */
    public function httpStatus(): int
    {
        return match ($this) {
            self::Ok => 200,
            self::Fail => 400,
            self::Unauthenticated => 401,
            self::Forbidden => 403,
            self::NotFound => 404,
            self::ValidationFailed => 422,
            self::TooManyRequests => 429,
            self::ServerError => 500,
        };
    }

    /**
     * 按当前语言返回文案，找不到翻译时回退到英文。
     */
    public function message(): string
    {
        $key = 'sunny::messages.'.$this->translationKey();
        $line = __($key);

        // 翻译缺失时 Laravel 会原样返回键名，这时改用内置英文。
        return $line === $key ? $this->defaultMessage() : $line;
    }

    /**
     * 把枚举名转换成语言包键，例如 ValidationFailed => validation_failed。
     */
    private function translationKey(): string
    {
        $name = preg_replace('/(?<!^)[A-Z]/', '_$0', $this->name);

        return strtolower(is_string($name) ? $name : $this->name);
    }

    /**
     * 语言包不可用时的英文默认文案。
     */
    private function defaultMessage(): string
    {
        return match ($this) {
            self::Ok => 'ok',
            self::Fail => 'The request could not be completed.',
            self::Unauthenticated => 'Unauthenticated.',
            self::Forbidden => 'This action is unauthorized.',
            self::NotFound => 'The resource was not found.',
            self::ValidationFailed => 'The given data was invalid.',
            self::TooManyRequests => 'Too many requests.',
            self::ServerError => 'Server error.',
        };
    }
}
