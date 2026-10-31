<?php

declare(strict_types=1);

/**
 * 不查数据库时使用的 JWT 身份
 *
 * 未配置 user_model 时，认证用户就是这个对象，标识来自令牌的 sub。
 *
 * @package     Sunny\Foundation\Auth
 * @license     MIT License (MIT)
 * @author      Sunny Yang <githup@163.com> QQ4873473
 * @copyright   Copyright (c) 2026 Sunny Yang. All rights reserved.
 * @link        http://www.budff.com
 */

namespace Sunny\Foundation\Auth;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * 没有用户模型时的临时身份，标识就是令牌里的 subject。
 */
final class JwtIdentity implements Authenticatable
{
    /**
     * @param  string  $id  令牌 subject
     * @param  array<string, mixed>  $claims
     */
    public function __construct(
        private readonly string $id,
        private readonly array $claims,
    ) {
    }

    /**
     * 认证标识字段名。
     */
    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    /**
     * 认证标识，即 sub。
     */
    public function getAuthIdentifier(): string
    {
        return $this->id;
    }

    /**
     * 本身份不保存密码。
     */
    public function getAuthPasswordName(): string
    {
        return 'password';
    }

    /**
     * 始终返回空字符串，避免被当作可登录的口令。
     */
    public function getAuthPassword(): string
    {
        return '';
    }

    /**
     * 不使用记住我令牌。
     */
    public function getRememberToken(): ?string
    {
        return null;
    }

    /**
     * JWT 身份不保存记住我令牌。
     */
    public function setRememberToken($value): void
    {
    }

    /**
     * 没有记住我字段。
     */
    public function getRememberTokenName(): string
    {
        return '';
    }

    /**
     * 令牌中的全部声明。
     *
     * @return array<string, mixed>
     */
    public function claims(): array
    {
        return $this->claims;
    }
}
