<?php

declare(strict_types=1);

namespace Sunny\Foundation\Auth;

use RuntimeException;
use Throwable;

final class JwtException extends RuntimeException
{
    private function __construct(
        string $message,
        private readonly string $reason,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public static function misconfigured(string $message): self
    {
        return new self($message, 'misconfigured');
    }

    public static function invalid(string $message = 'The token is invalid.', ?Throwable $previous = null): self
    {
        return new self($message, 'invalid', $previous);
    }

    public static function expired(?Throwable $previous = null): self
    {
        return new self('The token has expired.', 'expired', $previous);
    }

    public static function revoked(): self
    {
        return new self('The token has been revoked.', 'revoked');
    }

    public function reason(): string
    {
        return $this->reason;
    }

    public function isExpired(): bool
    {
        return $this->reason === 'expired';
    }
}
