<?php

declare(strict_types=1);

/**
 * 常用验证规则
 *
 * @package     Sunny\Foundation\Validation
 * @license     MIT License (MIT)
 * @author      Sunny Yang <githup@163.com> QQ4873473
 * @copyright   Copyright (c) 2026 Sunny Yang. All rights reserved.
 * @link        http://www.budff.com
 */

namespace Sunny\Foundation\Validation;

/**
 * 常用验证规则的组合。
 */
final class Rules
{
    /**
     * 禁止实例化，只通过静态方法组合规则。
     */
    private function __construct()
    {
    }

    /**
     * 中国大陆手机号规则。
     *
     * @return array<int, string|Mobile>
     */
    public static function mobile(): array
    {
        return ['string', new Mobile];
    }

    /**
     * 中国居民身份证号规则，包含校验位。
     *
     * @return array<int, string|ChineseIdCard>
     */
    public static function chineseIdCard(): array
    {
        return ['string', new ChineseIdCard];
    }

    /**
     * 小数规则。默认不允许负数，小数位数不超过 $scale。
     *
     * @return array<int, Decimal>
     */
    public static function decimal(int $scale = 2, bool $unsigned = true): array
    {
        return [new Decimal($scale, $unsigned)];
    }
}
