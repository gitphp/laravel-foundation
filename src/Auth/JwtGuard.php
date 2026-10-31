<?php

declare(strict_types=1);

namespace Sunny\Foundation\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Foundation\Application;
use Sunny\Foundation\Logging\ActivityLogger;

final class JwtGuard implements Guard
{
    private bool $resolved = false;

    private ?string $resolvedToken = null;

    private ?Authenticatable $user = null;

    public function __construct(
        private readonly Application $app,
        private readonly JwtManager $jwt,
        private readonly UserResolver $users,
    ) {
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function guest(): bool
    {
        return ! $this->check();
    }

    public function user(): ?Authenticatable
    {
        $token = $this->bearer();

        if ($this->resolved && $this->resolvedToken === $token) {
            return $this->user;
        }

        $this->resolved = true;
        $this->resolvedToken = $token;
        $this->user = $this->resolve($token);

        return $this->user;
    }

    public function id(): int|string|null
    {
        $id = $this->user()?->getAuthIdentifier();

        return is_int($id) || is_string($id) ? $id : null;
    }

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
            return $exception->reason() !== 'misconfigured' ? false : throw $exception;
        }
    }

    public function hasUser(): bool
    {
        return $this->resolved && $this->user !== null;
    }

    public function setUser(Authenticatable $user): void
    {
        $this->user = $user;
        $this->resolved = true;
        $this->resolvedToken = $this->bearer();
    }

    public function logout(): void
    {
        $this->jwt->logout();
        $this->user = null;
        $this->resolved = true;
        $this->resolvedToken = $this->bearer();
    }

    private function bearer(): ?string
    {
        $token = $this->app->make('request')->bearerToken();
        $token = is_string($token) ? trim($token) : '';

        return $token === '' ? null : $token;
    }

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

            $this->app->make(ActivityLogger::class)->info('jwt.rejected', [
                'reason' => $exception->reason(),
            ]);

            return null;
        }

        return $this->users->resolve($jwt);
    }
}
