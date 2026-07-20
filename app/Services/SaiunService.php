<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\CarbonImmutable;

readonly class SaiunService
{
    public function __construct(private RyunenService $ryunenService) {}

    /**
     * 指定された年の流年干支に、日干基準の通変星・十二運を付与する。
     * 吉凶評価としての歳運判断は次回以降に分離する。
     */
    public function calculate(int $year, int $dayStemId): array
    {
        return $this->ryunenService->getRyunenPillarByYear($year, $dayStemId);
    }

    public function calculateByDateTime(CarbonImmutable $dateTime, int $dayStemId): array
    {
        return $this->ryunenService->getRyunenPillarByDateTime($dateTime, $dayStemId);
    }
}
