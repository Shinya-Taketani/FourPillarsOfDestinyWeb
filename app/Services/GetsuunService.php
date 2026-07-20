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
        $monthBranches = [3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 1, 2];
        $monthNames = ['2月', '3月', '4月', '5月', '6月', '7月', '8月', '9月', '10月', '11月', '12月', '1月'];
        $yearStemId = $this->sexagenaryService->getPillarByYearNumber($year)['stem_id'];

        $months = [];
        foreach ($monthNames as $i => $name) {
            $monthPillar = $this->sexagenaryService->getMonthPillarIds($yearStemId, $monthBranches[$i]);
            $days = [];
            $calcYear = ($name === '1月') ? $year + 1 : $year;
            $mNum = ($name === '1月') ? 1 : ($i + 2);
            $startDate = CarbonImmutable::create($calcYear, $mNum, 1);

            for ($d = 0; $d < $startDate->daysInMonth; $d++) {
                $cur = $startDate->addDays($d);
                $dp = $this->sexagenaryService->getDayPillar($cur);
                $days[] = [
                    'day' => $cur->day,
                    'kanji' => ($this->masterData->getStemById($dp['stem_id'])->name ?? '')
                        .($this->masterData->getBranchById($dp['branch_id'])->name ?? ''),
                    'ten_god' => $this->starService->getTenGod($dayStemId, $dp['stem_id']),
                ];
            }

            $months[] = [
                'month_name' => $name,
                'stem_id' => $monthPillar['stem_id'],
                'branch_id' => $monthPillar['branch_id'],
                'kanji' => ($this->masterData->getStemById($monthPillar['stem_id'])->name ?? '')
                    .($this->masterData->getBranchById($monthPillar['branch_id'])->name ?? ''),
                'ten_god' => $this->starService->getTenGod($dayStemId, $monthPillar['stem_id']),
                'days' => $days,
            ];
        }

        // TODO: 月運期間を正節から次の正節までとするか要確認。現行の日運一覧はグレゴリオ暦月単位。
        return $months;
    }
}
