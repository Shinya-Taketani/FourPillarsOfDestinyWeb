<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\MasterDataRepository;
use Carbon\CarbonImmutable;
use RuntimeException;

readonly class ZokanService
{
    public function __construct(
        private MasterDataRepository $masterData,
    ) {}

    /**
     * 地支と節入りからの経過日数から、蔵干（天干ID）を特定する
     */
    public function getZokanStemId(int $branchId, CarbonImmutable $lmtDateTime, ?string $solarTermStartedAt): int
    {
        $ratios = $this->masterData->zokanRatiosByBranchId()->get($branchId, collect());

        if ($ratios->isEmpty()) {
            throw new RuntimeException("Zokan ratios are not seeded for branch_id {$branchId}.");
        }

        if (! $solarTermStartedAt) {
            $honki = $ratios->firstWhere('type', 'honki') ?? $ratios->last();

            return $this->requireStemId($honki, $branchId);
        }

        // solar_term_events.started_at は JST 壁時計値として保存されている。
        $startedAt = CarbonImmutable::parse($solarTermStartedAt, 'Asia/Tokyo');
        $diffDays = $startedAt->diffInDays($lmtDateTime);

        $currentDaysLimit = 0;
        foreach ($ratios as $ratio) {
            $currentDaysLimit += (int) $ratio->days;

            if ($diffDays <= $currentDaysLimit) {
                return $this->requireStemId($ratio, $branchId);
            }
        }

        // 期間を過ぎた場合は最後のデータを返す
        return $this->requireStemId($ratios->last(), $branchId);
    }

    private function requireStemId(object $ratio, int $branchId): int
    {
        if (! isset($ratio->stem_id)) {
            throw new RuntimeException("Zokan stem_id is not set for branch_id {$branchId}.");
        }

        return (int) $ratio->stem_id;
    }
}
