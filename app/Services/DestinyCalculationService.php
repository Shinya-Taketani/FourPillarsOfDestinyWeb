<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

readonly class DestinyCalculationService
{
    public function __construct(
        private LmtCalculatorService $lmtService,
        private SolarTermService $solarService,
        private StrengthCalculationService $strengthService,
        private StarCalculationService $starService,
        private SexagenaryService $sexagenaryService, // 追加
    ) {}

    public function analyze(string $birthDate, float $longitude): array
    {
        $lmtDateTime = $this->lmtService->calculate(CarbonImmutable::parse($birthDate), $longitude);
        $solar = $this->solarService->getSolarInfo($lmtDateTime);

        // 【本物化】各柱の干支を動的に計算
        $yearPillar  = $this->sexagenaryService->getYearPillar($lmtDateTime, $solar['term_name']);
        $dayPillar   = $this->sexagenaryService->getDayPillar($lmtDateTime);
        $hourPillar  = $this->sexagenaryService->getHourPillar($lmtDateTime, $dayPillar['stem_id']);
        
        // 月柱は節入り判定から取得（本来はもっと複雑ですが、まずは連動させます）
        $monthPillar = ['stem_id' => $solar['month_stem_id'], 'branch_id' => $solar['month_branch_id']];

        $pillarIds = [
            'year'  => $yearPillar,
            'month' => $monthPillar,
            'day'   => $dayPillar,
            'hour'  => $hourPillar,
        ];

        return [
            'lmt_datetime' => $lmtDateTime->toDateTimeString(),
            'solar_term' => $solar['term_name'],
            'pillars' => [
                'year'  => $this->formatPillar($pillarIds['year'], $dayPillar['stem_id']),
                'month' => $this->formatPillar($pillarIds['month'], $dayPillar['stem_id']),
                'day'   => $this->formatPillar($pillarIds['day'], $dayPillar['stem_id']),
                'hour'  => $this->formatPillar($pillarIds['hour'], $dayPillar['stem_id']),
            ],
            'five_elements_scores' => $this->strengthService->calculate($pillarIds, 1),
        ];
    }

    private function formatPillar(array $pillar, int $dayStemId): array
    {
        $stem = DB::table('master_stems')->where('id', $pillar['stem_id'])->first();
        $branch = DB::table('master_branches')->where('id', $pillar['branch_id'])->first();

        return [
            'kanji' => ($stem->name ?? '') . ($branch->name ?? ''),
            'ten_god' => $this->starService->getTenGod($dayStemId, $pillar['stem_id']),
            'twelve_life_stage' => $this->starService->getTwelveLifeStage($dayStemId, $pillar['branch_id']),
        ];
    }
}
