<?php

declare(strict_types=1);

namespace Sunny\Foundation\Auth;

use Illuminate\Contracts\Auth\Authenticatable;

final class JwtIdentity implements Authenticatable
{
    /**
     * @param  array<string, mixed>  $claims
     */
    public function __construct(
        private readonly string $id,
        private readonly array $claims,
    ) {
    }

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public function getAuthIdentifier(): string
    {
        return $this->id;
    }

    public function getAuthPasswordName(): string
    {
        return 'password';
    }

    public function getAuthPassword(): string
    {
        return '';
    }

    public function getRememberToken(): ?string
    {
        return null;
    }

    public function setRememberToken($value): void
    {
    }

    public function getRememberTokenName(): string
    {
        return '';
    }

    /**
     * @return array<string, mixed>
     */
    public function claims(): array
    {
        return $this->claims;
    }
}
