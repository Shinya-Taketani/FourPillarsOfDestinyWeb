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
        private SaiunService $saiunService,
        private GetsuunService $getsuunService,
        private RyunenService $ryunenService,
    ) {}

    public function analyze(string $birthDate, float $longitude, string $gender = 'male', string|CarbonImmutable|null $targetDateTime = null): array
    {
        $birthDateTimeJst = CarbonImmutable::parse($birthDate);
        $birthDateTimeLmt = $this->lmtService->calculate($birthDateTimeJst, $longitude);
        $targetDateTimeJst = $targetDateTime instanceof CarbonImmutable
            ? $targetDateTime
            : CarbonImmutable::parse($targetDateTime ?? now());

        // 節入りイベントは Asia/Tokyo の採用済み時刻なので、年柱・月柱は JST で比較する。
        $dateTimeForYearMonth = $birthDateTimeJst;
        // 現行仕様では、日柱の 23:00 日界と時柱の時支判定は LMT 補正後日時で行う。
        $dateTimeForDayHour = $birthDateTimeLmt;

        $dayPillar = $this->sexagenaryService->getDayPillar($dateTimeForDayHour);
        $yearPillar = $this->sexagenaryService->getYearPillar($dateTimeForYearMonth);
        $monthPillar = $this->sexagenaryService->getMonthPillar($dateTimeForYearMonth, $yearPillar['stem_id']);
        $solar = [
            'term_name' => $monthPillar['solar_term_name'],
            'month_stem_id' => $monthPillar['stem_id'],
            'month_branch_id' => $monthPillar['branch_id'],
            'started_at' => $monthPillar['started_at'],
        ];
        
        $pillarIds = [
            'year' => $yearPillar,
            'month' => ['stem_id' => $monthPillar['stem_id'], 'branch_id' => $monthPillar['branch_id']],
            'day' => $dayPillar,
            'hour' => $this->sexagenaryService->getHourPillar($dateTimeForDayHour, $dayPillar['stem_id']),
        ];

        $res = [
            'lmt_datetime' => $birthDateTimeLmt->toDateTimeString(),
            'solar_term' => $solar['term_name'],
            'pillars' => [
                'year' => $this->format($pillarIds['year'], $dayPillar['stem_id'], $birthDateTimeLmt, $solar['started_at']),
                'month' => $this->format($pillarIds['month'], $dayPillar['stem_id'], $birthDateTimeLmt, $solar['started_at']),
                'day' => $this->format($pillarIds['day'], $dayPillar['stem_id'], $birthDateTimeLmt, $solar['started_at']),
                'hour' => $this->format($pillarIds['hour'], $dayPillar['stem_id'], $birthDateTimeLmt, $solar['started_at']),
            ],
            'five_elements_scores' => $this->strengthService->calculate($pillarIds, (int)$solar['month_branch_id']),
        ];

        $ryunen = $this->ryunenService->getRyunenPillarByDateTime($targetDateTimeJst, $dayPillar['stem_id']);
        $res['saiun'] = $this->saiunService->calculate($ryunen['year'], $dayPillar['stem_id']);
        $res['getsuun'] = $this->getsuunService->calculate($ryunen['year'], $dayPillar['stem_id']);
        $res['dayun'] = $this->dayunService->calculate(
            $yearPillar,
            $pillarIds['month'],
            $dayPillar['stem_id'],
            $birthDateTimeJst,
            $gender,
        );
        $res['ryunen'] = $this->ryunenService->getRyunenWithActiveDayun(
            $birthDateTimeJst,
            $targetDateTimeJst,
            $res['dayun']['cycles'],
            $dayPillar['stem_id'],
        );
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
