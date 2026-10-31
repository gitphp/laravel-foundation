<?php

declare(strict_types=1);

namespace Sunny\Foundation\Tests\Http;

use Illuminate\Pagination\LengthAwarePaginator;
use Sunny\Foundation\Enums\ResponseCode;
use Sunny\Foundation\Http\Responses\ApiResponse;
use Sunny\Foundation\Tests\TestCase;

class ApiResponseTest extends TestCase
{
    public function test_it_returns_a_unicode_success_payload(): void
    {
        $data = ApiResponse::success(['name' => '晴'])->getData(true);

        $this->assertSame(0, $data['code']);
        $this->assertSame('ok', $data['message']);
        $this->assertSame('晴', $data['data']['name']);
        $this->assertArrayHasKey('request_id', $data);
    }

    public function test_it_returns_an_error_payload_with_http_status(): void
    {
        $response = ApiResponse::error('保存失败', ResponseCode::ValidationFailed, ['name' => ['必填']]);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame(42200, $response->getData(true)['code']);
        $this->assertSame('保存失败', $response->getData(true)['message']);
        $this->assertStringContainsString('保存失败', $response->getContent());
    }

    public function test_it_places_pagination_in_meta(): void
    {
        $paginator = new LengthAwarePaginator(
            items: [['id' => 1]],
            total: 21,
            perPage: 10,
            currentPage: 2,
        );

        $data = ApiResponse::paginate($paginator, 'ok')->getData(true);

        $this->assertSame([['id' => 1]], $data['data']);
        $this->assertSame(2, $data['meta']['current_page']);
        $this->assertSame(21, $data['meta']['total']);
        $this->assertSame(3, $data['meta']['last_page']);
    }
}
