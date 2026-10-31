<?php

declare(strict_types=1);

namespace Sunny\Foundation\Validation;

final class Rules
{
    private function __construct()
    {
    }

    /**
     * @return array<int, string|Mobile>
     */
    public static function mobile(): array
    {
        return ['string', new Mobile];
    }

    /**
     * @return array<int, string|ChineseIdCard>
     */
    public static function chineseIdCard(): array
    {
        return ['string', new ChineseIdCard];
    }

    /**
     * @return array<int, Decimal>
     */
    public static function decimal(int $scale = 2, bool $unsigned = true): array
    {
        return [new Decimal($scale, $unsigned)];
    }
}
