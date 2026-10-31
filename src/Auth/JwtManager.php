<?php

declare(strict_types=1);

namespace Sunny\Foundation\Auth;

use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;
use InvalidArgumentException;
use Sunny\Foundation\Cache\CacheRepository;
use Sunny\Foundation\Enums\JwtAlgorithm;
use Sunny\Foundation\Support\Toolkit;
use UnexpectedValueException;

final class JwtManager
{
    private const RESERVED = ['iss', 'aud', 'iat', 'nbf', 'exp', 'jti', 'typ', 'sub'];

    public function __construct(private readonly CacheRepository $cache)
    {
    }

    /**
     * @param  array<string, mixed>  $claims
     */
    public function issue(string|int $subject, array $claims = [], ?int $ttl = null): TokenPair
    {
        $subject = $this->subject($subject);
        $claims = $this->customClaims($claims);
        $accessTtl = $this->ttl('access', $ttl);
        $refreshTtl = $this->ttl('refresh', null);

        return new TokenPair(
            accessToken: $this->encode($subject, $claims, $accessTtl, 'access'),
            refreshToken: $this->encode($subject, $claims, $refreshTtl, 'refresh'),
            expiresIn: $accessTtl,
            refreshExpiresIn: $refreshTtl,
        );
    }

    public function parse(string $token, ?string $expectedType = 'access'): JwtToken
    {
        $this->assertTokenShape($token);

        $previousLeeway = JWT::$leeway;
        JWT::$leeway = $this->leeway();

        try {
            $decoded = JWT::decode($token, new Key($this->verificationKey(), $this->algorithm()->value));
        } catch (ExpiredException $exception) {
            throw JwtException::expired($exception);
        } catch (BeforeValidException|SignatureInvalidException|UnexpectedValueException|InvalidArgumentException $exception) {
            throw JwtException::invalid('The token is invalid.', $exception);
        } finally {
            JWT::$leeway = $previousLeeway;
        }

        $claims = json_decode(json_encode($decoded, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($claims)) {
            throw JwtException::invalid('The token is invalid.');
        }

        $jwt = JwtToken::from($claims);
        $this->assertRegisteredClaims($jwt, $expectedType);

        if ($this->cache->has($this->blacklistKey($jwt->id()))) {
            throw JwtException::revoked();
        }

        return $jwt;
    }

    /**
     * @param  array<string, mixed>  $claims
     */
    public function refresh(string $refreshToken): TokenPair
    {
        $token = $this->parse($refreshToken, 'refresh');
        $this->blacklist($token);

        return $this->issue($token->subject(), $token->customClaims());
    }

    public function invalidate(string $token): void
    {
        $this->blacklist($this->parse($token, null));
    }

    public function logout(?string $accessToken = null, ?string $refreshToken = null): void
    {
        $tokens = [$accessToken ?? $this->bearerFromRequest(), $refreshToken];

        foreach ($tokens as $token) {
            if (! is_string($token) || $token === '') {
                continue;
            }

            try {
                $this->invalidate($token);
            } catch (JwtException $exception) {
                if (! $exception->isExpired()) {
                    throw $exception;
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $claims
     */
    private function encode(string $subject, array $claims, int $ttl, string $type): string
    {
        $now = time();
        $payload = $claims;
        $payload['sub'] = $subject;
        $payload['iat'] = $now;
        $payload['nbf'] = $now;
        $payload['exp'] = $now + $ttl;
        $payload['jti'] = str_replace('-', '', Toolkit::uuid());
        $payload['typ'] = $type;

        $issuer = $this->issuer();

        if ($issuer !== null) {
            $payload['iss'] = $issuer;
        }

        $audience = config('sunny.jwt.audience');

        if (is_string($audience) && $audience !== '') {
            $payload['aud'] = $audience;
        }

        $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        if (strlen($encoded) > 4096) {
            throw new InvalidArgumentException('JWT claims are too large.');
        }

        return JWT::encode($payload, $this->signingKey(), $this->algorithm()->value);
    }

    private function blacklist(JwtToken $token): void
    {
        $seconds = $token->expiresAt()->getTimestamp() - time();

        if ($seconds <= 0) {
            return;
        }

        $this->cache->put($this->blacklistKey($token->id()), 1, $seconds);
    }

    private function blacklistKey(string $jti): string
    {
        return 'jwt:blacklist:'.$jti;
    }

    private function assertTokenShape(string $token): void
    {
        if ($token === '' || strlen($token) > 8192 || substr_count($token, '.') !== 2) {
            throw JwtException::invalid('The token is invalid.');
        }
    }

    private function assertRegisteredClaims(JwtToken $token, ?string $expectedType): void
    {
        foreach (['sub', 'iat', 'nbf', 'exp', 'jti', 'typ'] as $claim) {
            if (! array_key_exists($claim, $token->claims())) {
                throw JwtException::invalid('The token is invalid.');
            }
        }

        if (preg_match('/^[A-Za-z0-9_-]{16,128}$/', $token->id()) !== 1) {
            throw JwtException::invalid('The token is invalid.');
        }

        if (! in_array($token->type(), ['access', 'refresh'], true)) {
            throw JwtException::invalid('The token is invalid.');
        }

        if ($expectedType !== null && $token->type() !== $expectedType) {
            throw JwtException::invalid('The token is invalid.');
        }

        $issuer = $this->issuer();

        if ($issuer !== null && ($token->claims()['iss'] ?? null) !== $issuer) {
            throw JwtException::invalid('The token issuer is invalid.');
        }

        $audience = config('sunny.jwt.audience');

        if (is_string($audience) && $audience !== '') {
            $actual = $token->claims()['aud'] ?? null;
            $matches = $actual === $audience || (is_array($actual) && in_array($audience, $actual, true));

            if (! $matches) {
                throw JwtException::invalid('The token audience is invalid.');
            }
        }
    }

    /**
     * @param  array<string, mixed>  $claims
     * @return array<string, mixed>
     */
    private function customClaims(array $claims): array
    {
        foreach (self::RESERVED as $reserved) {
            unset($claims[$reserved]);
        }

        foreach ($claims as $key => $value) {
            if (! is_string($key) || preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $key) !== 1) {
                throw new InvalidArgumentException('JWT claim names must start with a letter and contain only letters, numbers, and underscores.');
            }

            $this->assertClaimValue($value);
        }

        return $claims;
    }

    private function assertClaimValue(mixed $value, int $depth = 0): void
    {
        if (is_string($value) || is_int($value) || is_bool($value) || $value === null) {
            return;
        }

        if (is_float($value)) {
            if (! is_finite($value)) {
                throw new InvalidArgumentException('JWT claims must be finite numbers.');
            }

            return;
        }

        if (is_array($value) && $depth < 3) {
            foreach ($value as $item) {
                $this->assertClaimValue($item, $depth + 1);
            }

            return;
        }

        throw new InvalidArgumentException('JWT claims must be scalars or arrays of scalars.');
    }

    private function subject(string|int $subject): string
    {
        if (is_string($subject)) {
            $subject = trim($subject);
        }

        $subject = (string) $subject;

        if ($subject === '' || strlen($subject) > 128) {
            throw new InvalidArgumentException('JWT subject must be between 1 and 128 characters.');
        }

        return $subject;
    }

    private function ttl(string $type, ?int $requested): int
    {
        $default = $type === 'refresh'
            ? (int) config('sunny.jwt.refresh_ttl', 1209600)
            : (int) config('sunny.jwt.ttl', 3600);
        $ttl = $requested ?? $default;
        $max = (int) config('sunny.jwt.ttl_max', 2592000);

        if ($max < 1 || $ttl < 1 || $ttl > $max) {
            throw JwtException::misconfigured('JWT TTL is outside the allowed range.');
        }

        return $ttl;
    }

    private function leeway(): int
    {
        $leeway = (int) config('sunny.jwt.leeway', 0);

        if ($leeway < 0 || $leeway > 300) {
            throw JwtException::misconfigured('JWT leeway must be between 0 and 300 seconds.');
        }

        return $leeway;
    }

    private function algorithm(): JwtAlgorithm
    {
        $value = config('sunny.jwt.algo', JwtAlgorithm::HS256->value);
        $algorithm = is_string($value) ? JwtAlgorithm::tryFrom($value) : null;

        if ($algorithm === null) {
            throw JwtException::misconfigured('Unsupported JWT algorithm. Use HS256 or RS256.');
        }

        return $algorithm;
    }

    private function signingKey(): string
    {
        return match ($this->algorithm()) {
            JwtAlgorithm::HS256 => $this->secret(),
            JwtAlgorithm::RS256 => $this->pem(config('sunny.jwt.private_key'), 'private'),
        };
    }

    private function verificationKey(): string
    {
        return match ($this->algorithm()) {
            JwtAlgorithm::HS256 => $this->secret(),
            JwtAlgorithm::RS256 => $this->pem(config('sunny.jwt.public_key'), 'public'),
        };
    }

    private function secret(): string
    {
        $secret = config('sunny.jwt.secret');

        if (! is_string($secret) || strlen($secret) < 32) {
            throw JwtException::misconfigured('Set SUNNY_JWT_SECRET to a random string of at least 32 characters.');
        }

        return $secret;
    }

    private function pem(mixed $value, string $kind): string
    {
        if (! is_string($value) || trim($value) === '') {
            throw JwtException::misconfigured('Set the JWT '.$kind.' key before using RS256.');
        }

        $value = str_replace('\\n', "\n", $value);

        if (is_file($value)) {
            $contents = file_get_contents($value);

            if ($contents === false || trim($contents) === '') {
                throw JwtException::misconfigured('Unable to read the JWT '.$kind.' key file.');
            }

            return $contents;
        }

        return $value;
    }

    private function issuer(): ?string
    {
        $issuer = config('sunny.jwt.issuer');

        if (! is_string($issuer) || $issuer === '') {
            $issuer = config('app.url');
        }

        return is_string($issuer) && $issuer !== '' ? $issuer : null;
    }

    private function bearerFromRequest(): ?string
    {
        if (! function_exists('request') || ! app()->bound('request')) {
            return null;
        }

        $token = request()->bearerToken();

        return is_string($token) && $token !== '' ? $token : null;
    }
}
