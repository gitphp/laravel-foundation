<?php

declare(strict_types=1);

namespace Sunny\Foundation\Tests\Auth;

use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Sunny\Foundation\Auth\JwtException;
use Sunny\Foundation\Auth\JwtManager;
use Sunny\Foundation\Tests\TestCase;

class JwtManagerTest extends TestCase
{
    public function test_it_issues_and_parses_an_access_token(): void
    {
        $manager = $this->app->make(JwtManager::class);
        $pair = $manager->issue(7, ['role' => 'admin']);
        $token = $manager->parse($pair->accessToken);

        $this->assertSame('7', $token->subject());
        $this->assertSame('access', $token->type());
        $this->assertSame('admin', $token->get('role'));
        $this->assertSame('Bearer', $pair->tokenType);
        $this->assertSame(3600, $pair->expiresIn);
    }

    public function test_it_rotates_refresh_tokens(): void
    {
        $manager = $this->app->make(JwtManager::class);
        $pair = $manager->issue('42', ['role' => 'admin']);
        $next = $manager->refresh($pair->refreshToken);

        $this->assertSame('42', $manager->parse($next->accessToken)->subject());
        $this->assertSame('admin', $manager->parse($next->accessToken)->get('role'));

        try {
            $manager->parse($pair->refreshToken, 'refresh');
            $this->fail('The previous refresh token should be revoked.');
        } catch (JwtException $exception) {
            $this->assertSame('revoked', $exception->reason());
        }
    }

    public function test_it_rejects_a_tampered_token_and_the_wrong_secret(): void
    {
        $manager = $this->app->make(JwtManager::class);
        $pair = $manager->issue('1');
        $parts = explode('.', $pair->accessToken);
        $parts[2] = substr($parts[2], 0, -1).($parts[2][-1] === 'a' ? 'b' : 'a');

        try {
            $manager->parse(implode('.', $parts));
            $this->fail('A tampered token should be rejected.');
        } catch (JwtException $exception) {
            $this->assertSame('invalid', $exception->reason());
        }

        config(['sunny.jwt.secret' => str_repeat('b', 48)]);

        try {
            $manager->parse($pair->accessToken);
            $this->fail('A token signed with another secret should be rejected.');
        } catch (JwtException $exception) {
            $this->assertSame('invalid', $exception->reason());
        }
    }

    public function test_it_rejects_expired_tokens_and_unsupported_algorithms(): void
    {
        $token = JWT::encode([
            'iss' => 'https://sunny.test',
            'sub' => '1',
            'iat' => time() - 100,
            'nbf' => time() - 100,
            'exp' => time() - 10,
            'jti' => 'expiredtokenjti01',
            'typ' => 'access',
        ], (string) config('sunny.jwt.secret'), 'HS256');

        try {
            $this->app->make(JwtManager::class)->parse($token);
            $this->fail('An expired token should be rejected.');
        } catch (JwtException $exception) {
            $this->assertTrue($exception->isExpired());
        }

        config(['sunny.jwt.algo' => 'none']);

        try {
            $this->app->make(JwtManager::class)->issue('1');
            $this->fail('The none algorithm should be rejected.');
        } catch (JwtException $exception) {
            $this->assertSame('misconfigured', $exception->reason());
        }
    }

    public function test_it_blacklists_tokens_on_logout(): void
    {
        $manager = $this->app->make(JwtManager::class);
        $pair = $manager->issue('5');
        $manager->logout($pair->accessToken, $pair->refreshToken);

        try {
            $manager->parse($pair->accessToken);
            $this->fail('The access token should be revoked.');
        } catch (JwtException $exception) {
            $this->assertSame('revoked', $exception->reason());
        }
    }

    public function test_it_rejects_an_empty_subject_and_oversized_claims(): void
    {
        $manager = $this->app->make(JwtManager::class);

        try {
            $manager->issue('   ');
            $this->fail('An empty subject should be rejected.');
        } catch (InvalidArgumentException) {
            $this->assertTrue(true);
        }

        $this->expectException(InvalidArgumentException::class);
        $manager->issue('1', ['note' => str_repeat('晴', 2000)]);
    }

    public function test_it_signs_with_rs256(): void
    {
        $options = [
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];
        $opensslConfig = dirname(PHP_BINARY).DIRECTORY_SEPARATOR.'extras'.DIRECTORY_SEPARATOR.'ssl'.DIRECTORY_SEPARATOR.'openssl.cnf';

        if (is_file($opensslConfig)) {
            $options['config'] = $opensslConfig;
        }

        $resource = openssl_pkey_new($options);

        if ($resource === false) {
            $this->markTestSkipped('OpenSSL could not generate a key: '.(openssl_error_string() ?: 'unknown'));
        }

        if (! openssl_pkey_export($resource, $private, null, $options)) {
            $this->markTestSkipped('OpenSSL could not export a private key.');
        }

        $details = openssl_pkey_get_details($resource);
        config([
            'sunny.jwt.algo' => 'RS256',
            'sunny.jwt.private_key' => $private,
            'sunny.jwt.public_key' => $details['key'] ?? '',
        ]);

        $manager = $this->app->make(JwtManager::class);
        $token = $manager->parse($manager->issue('9')->accessToken);

        $this->assertSame('9', $token->subject());
    }

    public function test_middleware_accepts_access_tokens_and_rejects_refresh_tokens(): void
    {
        $this->app['router']->get('/api/me', function (Request $request) {
            return sunny_success([
                'id' => $request->user()?->getAuthIdentifier(),
            ]);
        })->middleware('sunny.auth');

        $pair = $this->app->make(JwtManager::class)->issue(7);

        $this->getJson('/api/me')->assertUnauthorized()->assertJsonPath('code', 40100);

        $this->withToken($pair->accessToken)
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('data.id', '7');

        $this->withToken($pair->refreshToken)
            ->getJson('/api/me')
            ->assertUnauthorized();
    }
}
