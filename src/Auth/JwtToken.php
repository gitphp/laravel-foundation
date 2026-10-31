<?php

declare(strict_types=1);

/**
 * 已解析的 JWT
 *
 * 只保存声明，不保存原始令牌字符串，避免日志或异常把令牌带出去。
 *
 * @package     Sunny\Foundation\Auth
 * @license     MIT License (MIT)
 * @author      Sunny Yang <githup@163.com> QQ4873473
 * @copyright   Copyright (c) 2026 Sunny Yang. All rights reserved.
 * @link        http://www.budff.com
 */

namespace Sunny\Foundation\Auth;

use DateTimeImmutable;
use DateTimeZone;

/**
 * 已经通过签名和声明校验的令牌。
 */
final readonly class JwtToken
{
    /**
     * @param  array<string, mixed>  $claims  已解码的声明
     */
    private function __construct(private array $claims)
    {
    }

    /**
     * 用解码后的声明数组创建令牌对象。
     *
     * @param  array<string, mixed>  $claims
     */
    public static function from(array $claims): self
    {
        return new self($claims);
    }

    /**
     * 全部声明。
     *
     * @return array<string, mixed>
     */
    public function claims(): array
    {
        return $this->claims;
    }

    /**
     * 去掉注册声明后的业务字段，刷新令牌时会原样带过去。
     *
     * @return array<string, mixed>
     */
    public function customClaims(): array
    {
        return array_diff_key($this->claims, array_flip([
            'iss', 'aud', 'iat', 'nbf', 'exp', 'jti', 'typ', 'sub',
        ]));
    }

    /**
     * 用户标识，对应 sub。
     */
    public function subject(): string
    {
        $subject = $this->claims['sub'] ?? '';

        return is_scalar($subject) ? (string) $subject : '';
    }

    /**
     * 令牌编号，对应 jti，注销时写入黑名单。
     */
    public function id(): string
    {
        $id = $this->claims['jti'] ?? '';

        return is_scalar($id) ? (string) $id : '';
    }

    /**
     * 令牌类型，access 或 refresh。
     */
    public function type(): string
    {
        $type = $this->claims['typ'] ?? '';

        return is_scalar($type) ? (string) $type : '';
    }

    /**
     * 读取单个声明。
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->claims[$key] ?? $default;
    }

    /**
     * 过期时间，使用 UTC。
     */
    public function expiresAt(): DateTimeImmutable
    {
        return (new DateTimeImmutable('@'.(int) ($this->claims['exp'] ?? 0)))
            ->setTimezone(new DateTimeZone('UTC'));
    }
}
