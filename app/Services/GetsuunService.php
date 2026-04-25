<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;

readonly class GetsuunService
{
    public function __construct(private StarCalculationService $starService) {}

    /**
     * 2026年（丙午）の2月から翌1月までの月干支と星を算出
     */
    public function calculate(int $year, int $dayStemId): array
    {
        // 泰山流：丙の年は2月（正月）が庚寅からスタート
        $monthStems = [7, 8, 9, 10, 1, 2, 3, 4, 5, 6, 7, 8]; // 庚〜辛
        $monthBranches = [3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 1, 2]; // 寅〜丑
        $monthNames = ['2月', '3月', '4月', '5月', '6月', '7月', '8月', '9月', '10月', '11月', '12月', '1月'];

        $months = [];
        for ($i = 0; $i < 12; $i++) {
            $stemId = $monthStems[$i];
            $branchId = $monthBranches[$i];

            $stem = DB::table('master_stems')->where('id', $stemId)->first();
            $branch = DB::table('master_branches')->where('id', $branchId)->first();

            $months[] = [
                'month_name' => $monthNames[$i],
                'kanji' => ($stem->name ?? '') . ($branch->name ?? ''),
                'ten_god' => $this->starService->getTenGod($dayStemId, $stemId),
            ];
        }

        return $months;
    }
}
