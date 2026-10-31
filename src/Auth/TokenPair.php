<?php

declare(strict_types=1);

/**
 * 一次签发的访问令牌和刷新令牌
 *
 * @package     Sunny\Foundation\Auth
 * @license     MIT License (MIT)
 * @author      Sunny Yang <githup@163.com> QQ4873473
 * @copyright   Copyright (c) 2026 Sunny Yang. All rights reserved.
 * @link        http://www.budff.com
 */

namespace Sunny\Foundation\Auth;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * @implements Arrayable<string, int|string>
 */
/**
 * 一次签发得到的访问令牌和刷新令牌。
 */
final readonly class TokenPair implements Arrayable, JsonSerializable
{
    /**
     * @param  string  $accessToken  访问令牌
     * @param  string  $refreshToken  刷新令牌
     * @param  int  $expiresIn  访问令牌有效秒数
     * @param  int  $refreshExpiresIn  刷新令牌有效秒数
     */
    public function __construct(
        public string $accessToken,
        public string $refreshToken,
        public int $expiresIn,
        public int $refreshExpiresIn,
        public string $tokenType = 'Bearer',
    ) {
    }

    /**
     * 转成可直接放进 API data 的数组。
     *
     * @return array{token_type: string, access_token: string, refresh_token: string, expires_in: int, refresh_expires_in: int}
     */
    public function toArray(): array
    {
        return [
            'token_type' => $this->tokenType,
            'access_token' => $this->accessToken,
            'refresh_token' => $this->refreshToken,
            'expires_in' => $this->expiresIn,
            'refresh_expires_in' => $this->refreshExpiresIn,
        ];
    }

    /**
     * JSON 序列化时与 toArray() 保持一致。
     *
     * @return array{token_type: string, access_token: string, refresh_token: string, expires_in: int, refresh_expires_in: int}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
