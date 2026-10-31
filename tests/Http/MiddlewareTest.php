<?php

declare(strict_types=1);

/**
 * 全局中间件测试
 *
 * @package     Sunny\Foundation\Tests\Http
 * @license     MIT License (MIT)
 * @author      Sunny Yang <githup@163.com> QQ4873473
 * @copyright   Copyright (c) 2026 Sunny Yang. All rights reserved.
 * @link        http://www.budff.com
 */

namespace Sunny\Foundation\Tests\Http;

use Illuminate\Http\Request;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Sunny\Foundation\Tests\TestCase;

/**
 * 全局中间件测试
 */
class MiddlewareTest extends TestCase
{
    /**
     * 合法请求 ID 应保留，含换行的值应被替换。
     */
    public function test_it_assigns_and_preserves_a_request_id(): void
    {
        $this->app['router']->get('/ping', fn () => 'ok');

        $generated = $this->get('/ping');
        $generated->assertOk();
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            (string) $generated->headers->get('X-Request-Id'),
        );

        $supplied = $this->withHeader('X-Request-Id', 'trace-id-123456')->get('/ping');
        $supplied->assertHeader('X-Request-Id', 'trace-id-123456');

        $rejected = $this->withHeader('X-Request-Id', "bad\r\ninjected")->get('/ping');
        $this->assertDoesNotMatchRegularExpression("/[\r\n]/", (string) $rejected->headers->get('X-Request-Id'));
    }

    /**
     * 只有 api 路径被强制成 JSON。
     */
    public function test_it_forces_json_only_on_configured_paths(): void
    {
        $this->app['router']->get('/api/ping', function (Request $request) {
            return response()->json(['expects_json' => $request->expectsJson()]);
        });
        $this->app['router']->get('/web', function (Request $request) {
            return response()->json(['expects_json' => $request->expectsJson()]);
        });

        $this->get('/api/ping')->assertJsonPath('expects_json', true);
        $this->get('/web')->assertJsonPath('expects_json', false);
    }

    /**
     * 只记录 api 请求，页面路径不记访问日志。
     */
    public function test_it_logs_api_requests_and_skips_other_paths(): void
    {
        $logs = [];
        Event::listen(MessageLogged::class, function (MessageLogged $log) use (&$logs): void {
            $logs[] = $log;
        });

        $this->app['router']->get('/api/ping', fn () => sunny_success(['ok' => true]));
        $this->app['router']->get('/web', fn () => 'ok');

        $this->get('/api/ping')->assertOk();
        $this->get('/web')->assertOk();

        $paths = array_map(
            fn (MessageLogged $log): mixed => $log->context['path'] ?? null,
            array_values(array_filter($logs, fn (MessageLogged $log): bool => $log->message === 'http.request')),
        );

        $this->assertContains('/api/ping', $paths);
        $this->assertNotContains('/web', $paths);
    }
}

