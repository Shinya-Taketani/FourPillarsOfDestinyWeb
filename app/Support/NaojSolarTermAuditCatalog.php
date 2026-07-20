<?php

declare(strict_types=1);

namespace App\Support;

final class NaojSolarTermAuditCatalog
{
    public const SUPERSEDED_MATCHED = 'superseded_matched';

    public const SUPERSEDED_DISCREPANT = 'superseded_discrepant';

    public static function compare(string $annualStartedAt, string $longTermStartedAt): string
    {
        return $annualStartedAt === $longTermStartedAt
            ? self::SUPERSEDED_MATCHED
            : self::SUPERSEDED_DISCREPANT;
    }
}
