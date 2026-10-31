<?php

declare(strict_types=1);

namespace Sunny\Foundation\Auth;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * @implements Arrayable<string, int|string>
 */
final readonly class TokenPair implements Arrayable, JsonSerializable
{
    public function __construct(
        public string $accessToken,
        public string $refreshToken,
        public int $expiresIn,
        public int $refreshExpiresIn,
        public string $tokenType = 'Bearer',
    ) {
    }

    /**
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
     * @return array{token_type: string, access_token: string, refresh_token: string, expires_in: int, refresh_expires_in: int}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
