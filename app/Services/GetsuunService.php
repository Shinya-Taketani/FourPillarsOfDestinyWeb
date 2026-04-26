<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

readonly class GetsuunService
{
    public function __construct(
        private StarCalculationService $starService,
        private SexagenaryService $sexagenaryService
    ) {}

    /**
     * 指定された年の12ヶ月分の月運および、各月の日運を算出する
     */
    public function calculate(int $year, int $dayStemId): array
    {
        // 2026年（丙）の月干支スタート（2月 庚寅）
        $monthStems = [7, 8, 9, 10, 1, 2, 3, 4, 5, 6, 7, 8];
        $monthBranches = [3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 1, 2];
        $monthNames = ['2月', '3月', '4月', '5月', '6月', '7月', '8月', '9月', '10月', '11月', '12月', '1月'];

        $months = [];
        for ($i = 0; $i < 12; $i++) {
            $stemId = $monthStems[$i];
            $branchId = $monthBranches[$i];

            $stem = DB::table('master_stems')->where('id', $stemId)->first();
            $branch = DB::table('master_branches')->where('id', $branchId)->first();

            // 該当月の日運リストを生成
            $days = [];
            $calcYear = ($monthNames[$i] === '1月') ? $year + 1 : $year;
            $mNum = ($monthNames[$i] === '1月') ? 1 : ($i + 2);
            
            $startDate = CarbonImmutable::create($calcYear, $mNum, 1);
            $daysInMonth = $startDate->daysInMonth;

            for ($d = 0; $d < $daysInMonth; $d++) {
                $currentDate = $startDate->addDays($d);
                $dayPillar = $this->sexagenaryService->getDayPillar($currentDate);
                
                $dStem = DB::table('master_stems')->where('id', $dayPillar['stem_id'])->first();
                $dBranch = DB::table('master_branches')->where('id', $dayPillar['branch_id'])->first();

                $days[] = [
                    'day' => $currentDate->day,
                    'kanji' => ($dStem->name ?? '') . ($dBranch->name ?? ''),
                    'ten_god' => $this->starService->getTenGod($dayStemId, $dayPillar['stem_id']),
                    'twelve_life_stage' => $this->starService->getTwelveLifeStage($dayStemId, $dayPillar['branch_id']),
                ];
            }

            $months[] = [
                'month_name' => $monthNames[$i],
                'kanji' => ($stem->name ?? '') . ($branch->name ?? ''),
                'ten_god' => $this->starService->getTenGod($dayStemId, $stemId),
                'days' => $days,
            ];
        }

        return $months;
    }
}
