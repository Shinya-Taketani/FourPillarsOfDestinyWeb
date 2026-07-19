<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\Appraisal\AppraisalResultData;
use App\Repositories\MasterDataRepository;
use Carbon\CarbonImmutable;

readonly class DestinyCalculationService
{
    public function __construct(
        private LmtCalculatorService $lmtService,
        private SolarTermService $solarService,
        private FiveElementStrengthService $fiveElementStrengthService,
        private StarCalculationService $starService,
        private SexagenaryService $sexagenaryService,
        private ZokanService $zokanService,
        private AppraisalService $appraisalService,
        private DayunService $dayunService,
        private SaiunService $saiunService,
        private GetsuunService $getsuunService,
        private RyunenService $ryunenService,
        private RelationJudgementService $relationJudgementService,
        private JudgementService $judgementService,
        private MasterDataRepository $masterData,
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

        $fiveElementStrength = $this->fiveElementStrengthService->calculate(
            $pillarIds,
            (int) $solar['month_branch_id'],
        );

        $res = [
            'lmt_datetime' => $birthDateTimeLmt->toDateTimeString(),
            'solar_term' => $solar['term_name'],
            'pillars' => [
                'year' => $this->format($pillarIds['year'], $dayPillar['stem_id'], $birthDateTimeLmt, $solar['started_at']),
                'month' => $this->format($pillarIds['month'], $dayPillar['stem_id'], $birthDateTimeLmt, $solar['started_at']),
                'day' => $this->format($pillarIds['day'], $dayPillar['stem_id'], $birthDateTimeLmt, $solar['started_at']),
                'hour' => $this->format($pillarIds['hour'], $dayPillar['stem_id'], $birthDateTimeLmt, $solar['started_at']),
            ],
            'five_elements_scores' => $this->fiveElementStrengthService->compatibilityScores($fiveElementStrength),
            'five_element_strength' => $fiveElementStrength,
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
        $res['relations'] = $this->relationJudgementService->judgeNatalRelations($res);
        $res['judgement'] = $this->judgementService->evaluate($res);
        $res['appraisal'] = $this->appraisalService->generate($res, $res['five_elements_scores']);

        $warnings = $this->warnings();
        $inputDateTimeJst = CarbonImmutable::parse($birthDate, 'Asia/Tokyo');

        return AppraisalResultData::fromCalculation(
            legacyResult: $res,
            input: [
                'birth_date' => $inputDateTimeJst->toDateString(),
                'birth_time' => $inputDateTimeJst->format('H:i'),
                'gender' => $gender,
                'longitude' => $longitude,
                'input_timezone' => 'Asia/Tokyo',
                'target_year' => $targetDateTimeJst->year,
            ],
            calculationMetadata: $this->calculationMetadata(
                $birthDate,
                $birthDateTimeLmt,
                $monthPillar,
                $warnings,
            ),
            warnings: $warnings,
        )->toArray();
    }

    /**
     * @param  array{stem_id:int,branch_id:int}  $p
     */
    private function format(array $p, int $dsId, CarbonImmutable $lmt, mixed $start): array
    {
        $stem = $this->masterData->getStemById($p['stem_id']);
        $branch = $this->masterData->getBranchById($p['branch_id']);
        $zId = $this->zokanService->getZokanStemId($p['branch_id'], $lmt, $start);
        $zStem = $this->masterData->getStemById($zId);

        $stemName = (string) ($stem->name ?? '');
        $branchName = (string) ($branch->name ?? '');

        return [
            'stem_id' => $p['stem_id'],
            'branch_id' => $p['branch_id'],
            'stem_name' => $stemName,
            'branch_name' => $branchName,
            'pillar' => $stemName.$branchName,
            'kanji' => $stemName.$branchName,
            'ten_god' => ['name' => $this->starService->getTenGod($dsId, $p['stem_id'])],
            'zokan' => [
                'stem_id' => $zId,
                'name' => $zStem->name ?? '?',
                'ten_god_name' => $this->starService->getTenGod($dsId, $zId),
            ],
            'twelve_life_stage' => ['name' => $this->starService->getTwelveLifeStage($dsId, $p['branch_id'])],
        ];
    }

    /**
     * @param  array<string,mixed>  $monthPillar
     * @param  array<int,array{code:string,message:string,severity:string}>  $warnings
     * @return array<string,mixed>
     */
    private function calculationMetadata(
        string $birthDate,
        CarbonImmutable $birthDateTimeLmt,
        array $monthPillar,
        array $warnings,
    ): array {
        $inputDateTimeJst = CarbonImmutable::parse($birthDate, 'Asia/Tokyo');
        // 現行 LMT は JST の壁時計値を補正するため、API 上も Asia/Tokyo の壁時計値として明示する。
        $calculationDateTimeLmt = CarbonImmutable::parse(
            $birthDateTimeLmt->toDateTimeString(),
            'Asia/Tokyo',
        );
        $termTimeZone = (string) ($monthPillar['timezone'] ?? 'Asia/Tokyo');
        $termStartedAt = CarbonImmutable::parse((string) $monthPillar['started_at'], $termTimeZone);

        return [
            'calendar_system' => $monthPillar['calendar_system'] ?? null,
            'calendar_policy' => [
                'year_boundary' => 'lichun',
                'month_boundary' => 'major_solar_terms',
                'day_boundary' => '23:00',
                'hour_boundary' => 'two_hour_branches',
            ],
            'year_month_time_basis' => 'JST',
            'day_hour_time_basis' => 'LMT',
            'input_datetime_jst' => $inputDateTimeJst->toIso8601String(),
            'calculation_datetime_lmt' => $calculationDateTimeLmt->toIso8601String(),
            'adopted_solar_term_source' => [
                'term_name' => $monthPillar['solar_term_name'] ?? null,
                'started_at' => $termStartedAt->toIso8601String(),
                'timezone' => $termTimeZone,
                'title' => $monthPillar['source_title'] ?? null,
                'url' => $monthPillar['source_url'] ?? null,
            ],
            'adopted_solar_term_source_rank' => $monthPillar['source_rank'] ?? 'PENDING',
            'solar_term_adopted' => (bool) ($monthPillar['adopted'] ?? false),
            'day_boundary_mode' => '23:00',
            'rule_set' => config('taizan_judgement_rules.rule_set', 'taizan_pending'),
            'calculation_version' => (int) config('taizan_judgement_rules.version', 1),
            'warnings' => $warnings,
        ];
    }

    /** @return array<int,array{code:string,message:string,severity:string}> */
    private function warnings(): array
    {
        return [
            [
                'code' => 'TAIZAN_RULE_PENDING',
                'message' => '泰山流固有の身強身弱・格局・用神・喜忌判断は未確定です。',
                'severity' => 'warning',
            ],
            [
                'code' => 'SEASONAL_MULTIPLIER_PENDING',
                'message' => '五行力量の季節倍率と配点根拠は要確認です。',
                'severity' => 'warning',
            ],
            [
                'code' => 'CALENDAR_DATA_RANGE_LIMITED',
                'message' => '正式計算に利用できる採用済み節入りデータの対象年は限定されています。',
                'severity' => 'warning',
            ],
            [
                'code' => 'TIME_BASIS_REVIEW_REQUIRED',
                'message' => '日柱・時柱の境界判定に用いる LMT 時刻基準は要確認です。',
                'severity' => 'warning',
            ],
            [
                'code' => 'LUNAR_CALENDAR_UNAVAILABLE',
                'message' => '旧暦および天保壬寅元暦そのものは未実装です。',
                'severity' => 'info',
            ],
        ];
    }
}
