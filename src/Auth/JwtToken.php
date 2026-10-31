<?php

declare(strict_types=1);

namespace Sunny\Foundation\Auth;

use DateTimeImmutable;
use DateTimeZone;

final readonly class JwtToken
{
    /**
     * @param  array<string, mixed>  $claims
     */
    private function __construct(private array $claims)
    {
    }

    /**
     * @param  array<string, mixed>  $claims
     */
    public static function from(array $claims): self
    {
        return new self($claims);
    }

    /**
     * @return array<string, mixed>
     */
    public function claims(): array
    {
        return $this->claims;
    }

    /**
     * @return array<string, mixed>
     */
    public function customClaims(): array
    {
        return array_diff_key($this->claims, array_flip([
            'iss', 'aud', 'iat', 'nbf', 'exp', 'jti', 'typ', 'sub',
        ]));
    }

    public function subject(): string
    {
        $subject = $this->claims['sub'] ?? '';

        return is_scalar($subject) ? (string) $subject : '';
    }

    public function id(): string
    {
        $id = $this->claims['jti'] ?? '';

        return is_scalar($id) ? (string) $id : '';
    }

    public function type(): string
    {
        $type = $this->claims['typ'] ?? '';

        return is_scalar($type) ? (string) $type : '';
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->claims[$key] ?? $default;
    }

    public function expiresAt(): DateTimeImmutable
    {
        return (new DateTimeImmutable('@'.(int) ($this->claims['exp'] ?? 0)))
            ->setTimezone(new DateTimeZone('UTC'));
    }
}
