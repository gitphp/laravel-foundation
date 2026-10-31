<?php

declare(strict_types=1);

/**
 * JWT 管理器
 *
 * @package     Sunny\Foundation\Auth
 * @license     MIT License (MIT)
 * @author      Sunny Yang <githup@163.com> QQ4873473
 * @copyright   Copyright (c) 2026 Sunny Yang. All rights reserved.
 * @link        http://www.budff.com
 */

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

/**
 * JWT 管理器
 */
final class JwtManager
{
    private const RESERVED = ['iss', 'aud', 'iat', 'nbf', 'exp', 'jti', 'typ', 'sub'];

    /**
     * 黑名单存放在带前缀的缓存里。
     */
    public function __construct(private readonly CacheRepository $cache)
    {
    }

    /**
     * 为用户签发访问令牌和刷新令牌。
     *
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

    /**
     * 校验签名、过期时间、签发方和黑名单。默认只接受访问令牌。
     */
    public function parse(string $token, ?string $expectedType = 'access'): JwtToken
    {
        $this->assertTokenShape($token);

        // 时钟误差是 php-jwt 的全局静态值，验签结束后必须恢复。
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

        // 注销后的 jti 会留到原过期时间，这段时间内不能再使用。
        if ($this->cache->has($this->blacklistKey($jwt->id()))) {
            throw JwtException::revoked();
        }

        return $jwt;
    }

    /**
     * 用刷新令牌换一组新令牌，并立即作废旧的刷新令牌。
     *
     * @param  array<string, mixed>  $claims
     */
    public function refresh(string $refreshToken): TokenPair
    {
        $token = $this->parse($refreshToken, 'refresh');
        // 先作废旧的刷新令牌，再签发新的一对，避免旧令牌还能再换一次。
        $this->blacklist($token);

        return $this->issue($token->subject(), $token->customClaims());
    }

    /**
     * 把令牌编号写入黑名单，直到它原本的过期时间。
     */
    public function invalidate(string $token): void
    {
        $this->blacklist($this->parse($token, null));
    }

    /**
     * 注销访问令牌和可选的刷新令牌。已经过期的令牌视为注销成功。
     */
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
                // 已经过期的令牌不能再用，注销时不算失败。
                if (! $exception->isExpired()) {
                    throw $exception;
                }
            }
        }
    }

    /**
     * 写入注册声明并签名。调用方不能覆盖 exp、jti 等保留字段。
     *
     * @param  array<string, mixed>  $claims
     */
    private function encode(string $subject, array $claims, int $ttl, string $type): string
    {
        $now = time();
        $payload = $claims;
        // 保留声明只由服务端写入，调用方不能指定过期时间或令牌编号。
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

    /**
     * 在令牌剩余寿命内记住它的 jti。
     */
    private function blacklist(JwtToken $token): void
    {
        $seconds = $token->expiresAt()->getTimestamp() - time();

        if ($seconds <= 0) {
            return;
        }

        $this->cache->put($this->blacklistKey($token->id()), 1, $seconds);
    }

    /**
     * 黑名单缓存键。
     */
    private function blacklistKey(string $jti): string
    {
        return 'jwt:blacklist:'.$jti;
    }

    /**
     * 拒绝空令牌、过长令牌和段数不对的字符串。
     */
    private function assertTokenShape(string $token): void
    {
        if ($token === '' || strlen($token) > 8192 || substr_count($token, '.') !== 2) {
            throw JwtException::invalid('The token is invalid.');
        }
    }

    /**
     * 检查必要声明、令牌类型、签发方和受众。
     */
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
     * 去掉保留声明，并限制声明的名称和值。
     *
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

    /**
     * 声明只能是标量，或最多三层的标量数组。
     */
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

    /**
     * 整理用户标识。空白或超长时拒绝签发。
     */
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

    /**
     * 有效期必须在 1 秒和配置上限之间。
     */
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

    /**
     * 时钟误差最多 300 秒，避免把过期令牌放得太宽。
     */
    private function leeway(): int
    {
        $leeway = (int) config('sunny.jwt.leeway', 0);

        if ($leeway < 0 || $leeway > 300) {
            throw JwtException::misconfigured('JWT leeway must be between 0 and 300 seconds.');
        }

        return $leeway;
    }

    /**
     * 只接受 HS256 和 RS256。
     */
    private function algorithm(): JwtAlgorithm
    {
        $value = config('sunny.jwt.algo', JwtAlgorithm::HS256->value);
        $algorithm = is_string($value) ? JwtAlgorithm::tryFrom($value) : null;

        if ($algorithm === null) {
            throw JwtException::misconfigured('Unsupported JWT algorithm. Use HS256 or RS256.');
        }

        return $algorithm;
    }

    /**
     * 按算法选择签名密钥。
     */
    private function signingKey(): string
    {
        return match ($this->algorithm()) {
            JwtAlgorithm::HS256 => $this->secret(),
            JwtAlgorithm::RS256 => $this->pem(config('sunny.jwt.private_key'), 'private'),
        };
    }

    /**
     * 按算法选择验签密钥。HS256 与签名密钥相同。
     */
    private function verificationKey(): string
    {
        return match ($this->algorithm()) {
            JwtAlgorithm::HS256 => $this->secret(),
            JwtAlgorithm::RS256 => $this->pem(config('sunny.jwt.public_key'), 'public'),
        };
    }

    /**
     * HS256 密钥至少 32 个字符，不能使用空密钥。
     */
    private function secret(): string
    {
        $secret = config('sunny.jwt.secret');

        if (! is_string($secret) || strlen($secret) < 32) {
            throw JwtException::misconfigured('Set SUNNY_JWT_SECRET to a random string of at least 32 characters.');
        }

        return $secret;
    }

    /**
     * 读取 PEM 字符串或密钥文件。环境变量里的 \n 会还原成换行。
     */
    private function pem(mixed $value, string $kind): string
    {
        if (! is_string($value) || trim($value) === '') {
            throw JwtException::misconfigured('Set the JWT '.$kind.' key before using RS256.');
        }

        // .env 里的换行通常写成 \n 两个字符，这里还原成真正的换行。
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

    /**
     * 签发方。未单独配置时使用应用 URL。
     */
    private function issuer(): ?string
    {
        $issuer = config('sunny.jwt.issuer');

        if (! is_string($issuer) || $issuer === '') {
            $issuer = config('app.url');
        }

        return is_string($issuer) && $issuer !== '' ? $issuer : null;
    }

    /**
     * 从当前请求的 Authorization 头读取 Bearer 令牌。
     */
    private function bearerFromRequest(): ?string
    {
        if (! function_exists('request') || ! app()->bound('request')) {
            return null;
        }

        $token = request()->bearerToken();

        return is_string($token) && $token !== '' ? $token : null;
    }
}

