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
        private SexagenaryService $sexagenaryService,
        private ZokanService $zokanService,
        private AppraisalService $appraisalService,
        private DayunService $dayunService,
        private SaiunService $saiunService, // 追加
    ) {}

    public function analyze(string $birthDate, float $longitude, string $gender = 'male'): array
    {
        $lmt = $this->lmtService->calculate(CarbonImmutable::parse($birthDate), $longitude);
        $solar = $this->solarService->getSolarInfo($lmt);
        $dayPillar = $this->sexagenaryService->getDayPillar($lmt);
        $yearPillar = $this->sexagenaryService->getYearPillar($lmt, $solar);
        
        $pillarIds = [
            'year' => $yearPillar,
            'month' => ['stem_id' => $solar['month_stem_id'], 'branch_id' => $solar['month_branch_id']],
            'day' => $dayPillar,
            'hour' => $this->sexagenaryService->getHourPillar($lmt, $dayPillar['stem_id']),
        ];

        $res = [
            'lmt_datetime' => $lmt->toDateTimeString(),
            'solar_term' => $solar['term_name'],
            'pillars' => [
                'year' => $this->format($pillarIds['year'], $dayPillar['stem_id'], $lmt, $solar['started_at']),
                'month' => $this->format($pillarIds['month'], $dayPillar['stem_id'], $lmt, $solar['started_at']),
                'day' => $this->format($pillarIds['day'], $dayPillar['stem_id'], $lmt, $solar['started_at']),
                'hour' => $this->format($pillarIds['hour'], $dayPillar['stem_id'], $lmt, $solar['started_at']),
            ],
            'five_elements_scores' => $this->strengthService->calculate($pillarIds, (int)$solar['month_branch_id']),
        ];

        // 歳運（2026年）の計算
        $res['saiun'] = $this->saiunService->calculate(2026, $dayPillar['stem_id']);
        
        $res['dayun'] = $this->dayunService->calculate($yearPillar, $pillarIds['month'], $dayPillar['stem_id'], $lmt, $solar, $gender);
        $res['appraisal'] = $this->appraisalService->generate($res, $res['five_elements_scores']);

        return $res;
    }

    private function format($p, $dsId, $lmt, $start): array
    {
        $stem = DB::table('master_stems')->where('id', $p['stem_id'])->first();
        $branch = DB::table('master_branches')->where('id', $p['branch_id'])->first();
        $zId = $this->zokanService->getZokanStemId($p['branch_id'], $lmt, $start);
        $zStem = DB::table('master_stems')->where('id', $zId)->first();
        return [
            'kanji' => ($stem->name ?? '').($branch->name ?? ''),
            'ten_god' => ['name' => $this->starService->getTenGod($dsId, $p['stem_id'])],
            'zokan' => ['name' => $zStem->name ?? '?', 'ten_god_name' => $this->starService->getTenGod($dsId, $zId)],
            'twelve_life_stage' => ['name' => $this->starService->getTwelveLifeStage($dsId, $p['branch_id'])],
        ];
    }
}
