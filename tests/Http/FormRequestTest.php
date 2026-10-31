<?php

declare(strict_types=1);

/**
 * 表单请求测试
 *
 * @package     Sunny\Foundation\Tests\Http
 * @license     MIT License (MIT)
 * @author      Sunny Yang <githup@163.com> QQ4873473
 * @copyright   Copyright (c) 2026 Sunny Yang. All rights reserved.
 * @link        http://www.budff.com
 */

namespace Sunny\Foundation\Tests\Http;

use Sunny\Foundation\Tests\Fixtures\CreateUserRequest;
use Sunny\Foundation\Tests\Fixtures\DeniedRequest;
use Sunny\Foundation\Tests\TestCase;

/**
 * 表单请求测试
 */
class FormRequestTest extends TestCase
{
    /**
     * 验证失败应返回 422 和统一的错误结构。
     */
    public function test_it_returns_validation_errors_as_an_api_response(): void
    {
        $this->app['router']->post('/api/users', function (CreateUserRequest $request) {
            return sunny_success($request->validated());
        });

        $this->postJson('/api/users', [])
            ->assertStatus(422)
            ->assertJsonPath('code', 42200)
            ->assertJsonStructure(['errors' => ['name', 'mobile']]);
    }

    /**
     * 中文语言下应返回手机号规则的中文提示。
     */
    public function test_it_translates_a_custom_rule_message(): void
    {
        $this->app->setLocale('zh_CN');
        $this->app['router']->post('/api/users', function (CreateUserRequest $request) {
            return sunny_success($request->validated());
        });

        $this->postJson('/api/users', [
            'name' => '晴',
            'mobile' => '12345',
        ])->assertStatus(422)
            ->assertSee('必须是有效的手机号', false);
    }

    /**
     * 授权失败应返回 403。
     */
    public function test_it_returns_forbidden_when_authorization_fails(): void
    {
        $this->app['router']->post('/api/denied', function (DeniedRequest $request) {
            return sunny_success($request->validated());
        });

        $this->postJson('/api/denied', [])
            ->assertForbidden()
            ->assertJsonPath('code', 40300);
    }
}

