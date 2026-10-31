<?php

declare(strict_types=1);

namespace Sunny\Foundation\Tests\Http;

use Sunny\Foundation\Tests\Fixtures\CreateUserRequest;
use Sunny\Foundation\Tests\Fixtures\DeniedRequest;
use Sunny\Foundation\Tests\TestCase;

class FormRequestTest extends TestCase
{
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
