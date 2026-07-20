<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\CalendarDataUnavailableException;
use App\Repositories\MasterDataRepository;
use Carbon\CarbonImmutable;
use InvalidArgumentException;

readonly class DayunService
{
    private const DIRECTION_FORWARD = 'forward';

    private const DIRECTION_BACKWARD = 'backward';

    private const CYCLE_COUNT = 10;

    private const CYCLE_YEARS = 10;

    private const SECONDS_PER_DAY = 86400;

    public function __construct(
        private StarCalculationService $starService,
        private SolarTermService $solarTermService,
        private InterpretationDictionaryService $dictionary,
        private MasterDataRepository $masterData,
    ) {}

    public function calculate(
        array $yearPillar,
        array $monthPillar,
        int $dayStemId,
        CarbonImmutable $birthDateTimeJst,
        string $gender,
    ): array {
        $direction = $this->determineDirection((int) $yearPillar['stem_id'], $gender);
        $basisTerm = $this->getBasisTerm($birthDateTimeJst, $direction);
        $startAge = $this->calculateStartAge($birthDateTimeJst, CarbonImmutable::parse($basisTerm->started_at, $basisTerm->timezone));
        $cycles = $this->buildCycles($monthPillar, $dayStemId, $direction, $startAge['start_age_years_decimal']);

        return [
            'direction' => $direction,
            'is_forward' => $direction === self::DIRECTION_FORWARD,
            'basis_term' => [
                'name' => $basisTerm->term_name,
                'started_at' => $basisTerm->started_at,
                'timezone' => $basisTerm->timezone,
            ],
            'basis_term_name' => $basisTerm->term_name,
            'basis_term_started_at' => $basisTerm->started_at,
            'start_age' => $startAge['start_age_years'],
            'start_age_full' => "{$startAge['start_age_years']}歳 {$startAge['start_age_months']}ヶ月",
            'start_age_years_decimal' => $startAge['start_age_years_decimal'],
            'start_age_years' => $startAge['start_age_years'],
            'start_age_months' => $startAge['start_age_months'],
            'calculation_note' => $this->dictionary->text('calculation_notes.dayun_start_age', 'calculation_notes.dayun_start_age'),
            'cycles' => $cycles,
        ];
    }

    public function determineDirection(int $yearStemId, string|int $gender): string
    {
        $normalizedGender = $this->normalizeGender($gender);
        $isForward = $normalizedGender === 'male'
            ? $this->isYangStem($yearStemId)
            : ! $this->isYangStem($yearStemId);

        return $isForward ? self::DIRECTION_FORWARD : self::DIRECTION_BACKWARD;
    }

    public function isYangStem(int $stemId): bool
    {
        if ($stemId < 1 || $stemId > 10) {
            throw new InvalidArgumentException('年干 ID が不正です。');
        }

        return $stemId % 2 === 1;
    }

    public function calculateStartAge(CarbonImmutable $birthDateTimeJst, CarbonImmutable $basisTermDateTime): array
    {
        // TODO: 起運年齢の細かい日・時刻換算は泰山流固有ルール要確認。
        $diffSeconds = abs($birthDateTimeJst->diffInSeconds($basisTermDateTime, false));
        $diffDays = $diffSeconds / self::SECONDS_PER_DAY;
        $startAgeYearsDecimal = round($diffDays / 3, 4);
        $totalMonths = (int) round($startAgeYearsDecimal * 12);

        return [
            'start_age_years_decimal' => $startAgeYearsDecimal,
            'start_age_years' => intdiv($totalMonths, 12),
            'start_age_months' => $totalMonths % 12,
        ];
    }

    private function getBasisTerm(CarbonImmutable $birthDateTimeJst, string $direction): object
    {
        // 起運計算の正節比較は、採用済み solar_term_events の JST 時刻で行う。
        $event = $direction === self::DIRECTION_FORWARD
            ? $this->solarTermService->getNextMonthBoundaryEvent($birthDateTimeJst)
            : $this->solarTermService->getPreviousMonthBoundaryEvent($birthDateTimeJst);

        if ($event === null) {
            throw CalendarDataUnavailableException::forMonthBoundary($birthDateTimeJst->toDateTimeString());
        }

        return $event;
    }

    private function buildCycles(array $monthPillar, int $dayStemId, string $direction, float $startAgeYearsDecimal): array
    {
        $currentStemId = (int) ($monthPillar['stem_id'] ?? 0);
        $currentBranchId = (int) ($monthPillar['branch_id'] ?? 0);

        if ($currentStemId < 1 || $currentStemId > 10 || $currentBranchId < 1 || $currentBranchId > 12) {
            throw new InvalidArgumentException('月柱が不正です。');
        }

        $cycles = [];

        for ($i = 0; $i < self::CYCLE_COUNT; $i++) {
            [$currentStemId, $currentBranchId] = $this->movePillar($currentStemId, $currentBranchId, $direction);
            $startAge = round($startAgeYearsDecimal + ($i * self::CYCLE_YEARS), 4);
            $endAge = round($startAge + self::CYCLE_YEARS, 4);
            $stemName = $this->masterData->getStemById($currentStemId)?->name;
            $branchName = $this->masterData->getBranchById($currentBranchId)?->name;

            if ($stemName === null || $branchName === null) {
                throw new InvalidArgumentException('干支マスターが不足しています。');
            }

            // TODO: 泰山流の大運起点は月柱の次/前でよいか要確認。
            $cycles[] = [
                'index' => $i + 1,
                'pillar' => $stemName.$branchName,
                'kanji' => $stemName.$branchName,
                'stem_id' => $currentStemId,
                'branch_id' => $currentBranchId,
                'stem_name' => $stemName,
                'branch_name' => $branchName,
                'start_age_years' => $startAge,
                'end_age_years' => $endAge,
                'start_age_months' => (int) round($startAge * 12),
                'direction' => $direction,
                'age' => (int) floor($startAge),
                'ten_god' => $this->starService->getTenGod($dayStemId, $currentStemId),
            ];
        }

        return $cycles;
    }

    private function movePillar(int $stemId, int $branchId, string $direction): array
    {
        if ($direction === self::DIRECTION_FORWARD) {
            return [($stemId % 10) + 1, ($branchId % 12) + 1];
        }

        return [
            $stemId === 1 ? 10 : $stemId - 1,
            $branchId === 1 ? 12 : $branchId - 1,
        ];
    }

    private function normalizeGender(string|int $gender): string
    {
        return match ($gender) {
            'male', 'm', '1', 1 => 'male',
            'female', 'f', '2', 2 => 'female',
            default => throw new InvalidArgumentException('gender は male/female/1/2 のいずれかで指定してください。'),
        };
    }
}
