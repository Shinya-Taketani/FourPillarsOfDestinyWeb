<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;

readonly class SaiunService
{
    public function __construct(private StarCalculationService $starService) {}

    /**
     * 指定された年の歳運データを取得する
     */
    public function calculate(int $year, int $dayStemId): array
    {
        // 干支の計算 (2026 -> 丙午)
        $index = ($year - 3) % 60;
        if ($index <= 0) $index += 60;

        $stemId = $index % 10 ?: 10;
        $branchId = $index % 12 ?: 12;

        $stem = DB::table('master_stems')->where('id', $stemId)->first();
        $branch = DB::table('master_branches')->where('id', $branchId)->first();

        return [
            'year' => $year,
            'kanji' => ($stem->name ?? '') . ($branch->name ?? ''),
            'ten_god' => $this->starService->getTenGod($dayStemId, $stemId),
            'twelve_life_stage' => $this->starService->getTwelveLifeStage($dayStemId, $branchId),
        ];
    }
}
