<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\MasterDataRepository;
use Carbon\CarbonImmutable;

readonly class GetsuunService
{
    public function __construct(
        private StarCalculationService $starService,
        private SexagenaryService $sexagenaryService,
        private MasterDataRepository $masterData,
    ) {}

    public function calculate(int $year, int $dayStemId): array
    {
        $stems = $this->masterData->stemsById();
        $branches = $this->masterData->branchesById();

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
                    'kanji' => ($stems->get($dp['stem_id'])?->name ?? '').($branches->get($dp['branch_id'])?->name ?? ''),
                    'ten_god' => $this->starService->getTenGod($dayStemId, $dp['stem_id']),
                ];
            }

            $months[] = [
                'month_name' => $name,
                'kanji' => ($stems->get($monthStems[$i])?->name ?? '').($branches->get($monthBranches[$i])?->name ?? ''),
                'ten_god' => $this->starService->getTenGod($dayStemId, $monthStems[$i]),
                'days' => $days,
            ];
        }
        return $months;
    }
}
