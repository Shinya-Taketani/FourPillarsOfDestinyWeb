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

    public function calculate(int $year, int $dayStemId): array
    {
        $stems = DB::table('master_stems')->pluck('name', 'id');
        $branches = DB::table('master_branches')->pluck('name', 'id');

        $monthStems = [7, 8, 9, 10, 1, 2, 3, 4, 5, 6, 7, 8]; 
        $monthBranches = [3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 1, 2];
        $monthNames = ['2月', '3月', '4月', '5月', '6月', '7月', '8月', '9月', '10月', '11月', '12月', '1月'];

        $months = [];
        foreach ($monthNames as $i => $name) {
            $days = [];
            $calcYear = ($name === '1月') ? $year + 1 : $year;
            $mNum = ($name === '1月') ? 1 : ($i + 2);
            $startDate = CarbonImmutable::create($calcYear, $mNum, 1);
            
            for ($d = 0; $d < $startDate->daysInMonth; $d++) {
                $cur = $startDate->addDays($d);
                $dp = $this->sexagenaryService->getDayPillar($cur);
                $days[] = [
                    'day' => $cur->day,
                    'kanji' => ($stems[$dp['stem_id']] ?? '') . ($branches[$dp['branch_id']] ?? ''),
                    'ten_god' => $this->starService->getTenGod($dayStemId, $dp['stem_id']),
                ];
            }

            $months[] = [
                'month_name' => $name,
                'kanji' => ($stems[$monthStems[$i]] ?? '') . ($branches[$monthBranches[$i]] ?? ''),
                'ten_god' => $this->starService->getTenGod($dayStemId, $monthStems[$i]),
                'days' => $days,
            ];
        }
        return $months;
    }
}
