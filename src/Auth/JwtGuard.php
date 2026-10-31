<?php

declare(strict_types=1);

/**
 * JWT 守卫
 *
 * @package     Sunny\Foundation\Auth
 * @license     MIT License (MIT)
 * @author      Sunny Yang <githup@163.com> QQ4873473
 * @copyright   Copyright (c) 2026 Sunny Yang. All rights reserved.
 * @link        http://www.budff.com
 */

namespace Sunny\Foundation\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Foundation\Application;
use Sunny\Foundation\Logging\ActivityLogger;

/**
 * JWT 守卫
 */
final class JwtGuard implements Guard
{
    private bool $resolved = false;

    private ?string $resolvedToken = null;

    private ?Authenticatable $user = null;

    /**
     * 守卫在每次取用户时读取当前请求，避免拿到来不及更新的旧请求。
     */
    public function __construct(
        private readonly Application $app,
        private readonly JwtManager $jwt,
        private readonly UserResolver $users,
    ) {
    }

    /**
     * 当前请求是否已经通过 JWT 认证。
     */
    public function check(): bool
    {
        return $this->user() !== null;
    }

    /**
     * 当前请求是否还没有认证用户。
     */
    public function guest(): bool
    {
        return ! $this->check();
    }

    /**
     * 解析访问令牌并解析用户。令牌变了会重新解析。
     */
    public function user(): ?Authenticatable
    {
        $token = $this->bearer();

        // 只有 Bearer 没变才复用上次的用户。同一个应用里更换令牌必须重新解析。
        if ($this->resolved && $this->resolvedToken === $token) {
            return $this->user;
        }

        $this->resolved = true;
        $this->resolvedToken = $token;
        $this->user = $this->resolve($token);

        return $this->user;
    }

    /**
     * 当前用户标识。
     */
    public function id(): int|string|null
    {
        $id = $this->user()?->getAuthIdentifier();

        return is_int($id) || is_string($id) ? $id : null;
    }

    /**
     * 检查一组凭据里的访问令牌是否有效。
     */
    public function validate(array $credentials = []): bool
    {
        $token = $credentials['token'] ?? null;

        if (! is_string($token) || trim($token) === '') {
            return false;
        }

        try {
            $this->jwt->parse(trim($token), 'access');

            return true;
        } catch (JwtException $exception) {
            // 密钥或算法没配好属于服务错误，不能伪装成令牌无效。
            return $exception->reason() !== 'misconfigured' ? false : throw $exception;
        }
    }

    /**
     * 是否已经解析出用户实例。
     */
    public function hasUser(): bool
    {
        return $this->resolved && $this->user !== null;
    }

    /**
     * 直接设置当前用户。
     */
    public function setUser(Authenticatable $user): void
    {
        $this->user = $user;
        $this->resolved = true;
        $this->resolvedToken = $this->bearer();
    }

    /**
     * 注销当前 Bearer 令牌，并清空守卫里的用户。
     */
    public function logout(): void
    {
        $this->jwt->logout();
        $this->user = null;
        $this->resolved = true;
        $this->resolvedToken = $this->bearer();
    }

    /**
     * 读取并去掉空白后的 Bearer 令牌。
     */
    private function bearer(): ?string
    {
        $token = $this->app->make('request')->bearerToken();
        $token = is_string($token) ? trim($token) : '';

        return $token === '' ? null : $token;
    }

    /**
     * 解析访问令牌。配置错误继续抛出，其余失败记日志并当作未登录。
     */
    private function resolve(?string $token): ?Authenticatable
    {
        if ($token === null) {
            return null;
        }

        try {
            $jwt = $this->jwt->parse($token, 'access');
        } catch (JwtException $exception) {
            if ($exception->reason() === 'misconfigured') {
                throw $exception;
            }

            // 只记录失败原因，不记录令牌本身。
            $this->app->make(ActivityLogger::class)->info('jwt.rejected', [
                'reason' => $exception->reason(),
            ]);

            return null;
        }

        return $this->users->resolve($jwt);
    }
}

