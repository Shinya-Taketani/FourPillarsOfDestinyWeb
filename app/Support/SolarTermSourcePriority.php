<?php

declare(strict_types=1);

namespace App\Support;

final class SolarTermSourcePriority
{
    /**
     * S1: 各年暦要項・暦象年表による年次公式公表値。
     * S2: 国立天文台「二十四節気・雑節 長期版」。
     *
     * @var array<string,int>
     */
    public const PRIORITIES = [
        'S1' => 100,
        'S2' => 50,
    ];

    public static function priority(string $sourceRank): ?int
    {
        return self::PRIORITIES[$sourceRank] ?? null;
    }

    public static function isKnown(string $sourceRank): bool
    {
        return self::priority($sourceRank) !== null;
    }
}
