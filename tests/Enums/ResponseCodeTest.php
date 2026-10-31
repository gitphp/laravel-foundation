<?php

declare(strict_types=1);

namespace Sunny\Foundation\Tests\Enums;

use Sunny\Foundation\Enums\ResponseCode;
use Sunny\Foundation\Tests\TestCase;

class ResponseCodeTest extends TestCase
{
    public function test_it_maps_codes_to_http_status_and_translated_messages(): void
    {
        $this->assertSame(200, ResponseCode::Ok->httpStatus());
        $this->assertSame(422, ResponseCode::ValidationFailed->httpStatus());
        $this->assertSame('Unauthenticated.', ResponseCode::Unauthenticated->message());

        $this->app->setLocale('zh_CN');

        $this->assertSame('未登录或登录已失效。', ResponseCode::Unauthenticated->message());
    }
}
