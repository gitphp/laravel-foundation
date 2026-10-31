<?php

declare(strict_types=1);

/**
 * 配置读取
 *
 * @package     Sunny\Foundation\Config
 * @license     MIT License (MIT)
 * @author      Sunny Yang <githup@163.com> QQ4873473
 * @copyright   Copyright (c) 2026 Sunny Yang. All rights reserved.
 * @link        http://www.budff.com
 */

namespace Sunny\Foundation\Config;

/**
 * 配置读取
 */
final class ConfigRepository
{
    /**
     * 返回 sunny 配置的全部内容。
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $all = config('sunny', []);

        return is_array($all) ? $all : [];
    }

    /**
     * 按点号键读取一项配置。
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return config('sunny.'.$key, $default);
    }

    /**
     * 判断配置键是否存在。
     */
    public function has(string $key): bool
    {
        return config()->has('sunny.'.$key);
    }

    /**
     * 读取字符串配置。值不是标量时返回默认值。
     */
    public function string(string $key, string $default = ''): string
    {
        $value = $this->get($key, $default);

        return is_scalar($value) ? (string) $value : $default;
    }

    /**
     * 读取整数配置。无法转换时返回默认值。
     */
    public function int(string $key, int $default = 0): int
    {
        $value = $this->get($key, $default);

        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && preg_match('/^-?\d+$/', $value) === 1) {
            return (int) $value;
        }

        return $default;
    }

    /**
     * 读取布尔配置。字符串 false 会被当成假。
     */
    public function bool(string $key, bool $default = false): bool
    {
        $value = $this->get($key, $default);

        if (is_bool($value)) {
            return $value;
        }

        if (! is_scalar($value)) {
            return $default;
        }

        $parsed = filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

        return $parsed ?? $default;
    }

    /**
     * 读取数组配置。值不是数组时返回默认值。
     *
     * @param  array<mixed>  $default
     * @return array<mixed>
     */
    public function array(string $key, array $default = []): array
    {
        $value = $this->get($key, $default);

        return is_array($value) ? $value : $default;
    }
}

