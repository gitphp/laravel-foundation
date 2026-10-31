<?php

declare(strict_types=1);

/**
 * JWT 异常
 *
 * reason 只使用 misconfigured、invalid、expired、revoked，避免把令牌内容返回给调用方。
 *
 * @package     Sunny\Foundation\Auth
 * @license     MIT License (MIT)
 * @author      Sunny Yang <githup@163.com> QQ4873473
 * @copyright   Copyright (c) 2026 Sunny Yang. All rights reserved.
 * @link        http://www.budff.com
 */

namespace Sunny\Foundation\Auth;

use RuntimeException;
use Throwable;

/**
 * JWT 异常。用 reason 区分配置错误、无效、过期和已注销。
 */
final class JwtException extends RuntimeException
{
    /**
     * @param  string  $reason  misconfigured、invalid、expired、revoked 之一
     */
    private function __construct(
        string $message,
        private readonly string $reason,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    /**
     * 密钥或算法配置不正确。
     */
    public static function misconfigured(string $message): self
    {
        return new self($message, 'misconfigured');
    }

    /**
     * 令牌签名、声明或类型无效。
     */
    public static function invalid(string $message = 'The token is invalid.', ?Throwable $previous = null): self
    {
        return new self($message, 'invalid', $previous);
    }

    /**
     * 令牌已过期。
     */
    public static function expired(?Throwable $previous = null): self
    {
        return new self('The token has expired.', 'expired', $previous);
    }

    /**
     * 令牌已注销，标识存在于缓存黑名单。
     */
    public static function revoked(): self
    {
        return new self('The token has been revoked.', 'revoked');
    }

    /**
     * 返回机器可读的失败原因。
     */
    public function reason(): string
    {
        return $this->reason;
    }

    /**
     * 是否因为过期被拒绝。注销已过期的令牌时可以忽略这个错误。
     */
    public function isExpired(): bool
    {
        return $this->reason === 'expired';
    }
}
