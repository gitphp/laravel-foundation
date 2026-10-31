<?php

declare(strict_types=1);

/**
 * 业务状态码测试
 *
 * @package     Sunny\Foundation\Tests\Enums
 * @license     MIT License (MIT)
 * @author      Sunny Yang <githup@163.com> QQ4873473
 * @copyright   Copyright (c) 2026 Sunny Yang. All rights reserved.
 * @link        http://www.budff.com
 */

namespace Sunny\Foundation\Tests\Enums;

use Sunny\Foundation\Enums\ResponseCode;
use Sunny\Foundation\Tests\TestCase;

/**
 * 业务状态码测试
 */
class ResponseCodeTest extends TestCase
{
    /**
     * 业务码应映射到 HTTP 状态，并随语言切换文案。
     */
    public function test_it_maps_codes_to_http_status_and_translated_messages(): void
    {
        $this->assertSame(200, ResponseCode::Ok->httpStatus());
        $this->assertSame(422, ResponseCode::ValidationFailed->httpStatus());
        $this->assertSame('Unauthenticated.', ResponseCode::Unauthenticated->message());

        $this->app->setLocale('zh_CN');

        $this->assertSame('未登录或登录已失效。', ResponseCode::Unauthenticated->message());
    }
}

