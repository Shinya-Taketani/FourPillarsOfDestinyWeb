<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\CalendarDataUnavailableException;
use Carbon\CarbonImmutable;

/**
 * 干支計算サービス（精密版）
 */
readonly class SexagenaryService
{
    public function __construct(
        private SolarTermService $solarTermService,
    ) {}

    /**
     * 年の干支を算出
     */
    public function getYearPillar(CarbonImmutable $date): array
    {
        $year = $this->getPillarYear($date);

        $index = ($year - 3) % 60;
        if ($index <= 0) $index += 60;

        return $this->splitIndex($index);
    }

    public function getPillarYear(CarbonImmutable $date): int
    {
        $lichun = $this->solarTermService->getLichunDateTime($date->year);

        if ($lichun === null) {
            throw CalendarDataUnavailableException::forLichun($date->year);
        }

        $localDate = CarbonImmutable::parse($date->toDateTimeString(), $lichun->timezoneName);

        return $localDate->lt($lichun) ? $date->year - 1 : $date->year;
    }

    public function getMonthPillar(CarbonImmutable $date, int $yearStemId): array
    {
        $event = $this->solarTermService->getLatestMonthBoundaryEvent($date);

        if ($event === null) {
            throw CalendarDataUnavailableException::forMonthBoundary($date->toDateTimeString());
        }

        $branchId = (int)$event->month_branch_id;
        $startStemId = (($yearStemId - 1) % 5) * 2 + 3;

        if ($startStemId > 10) {
            $startStemId -= 10;
        }

        $offset = $branchId - 3;
        if ($offset < 0) {
            $offset += 12;
        }

        return [
            'stem_id' => (($startStemId + $offset - 1) % 10) + 1,
            'branch_id' => $branchId,
            'solar_term_name' => $event->term_name,
            'started_at' => $event->started_at,
        ];
    }

    public function getDayPillar(CarbonImmutable $date): array
    {
        $baseDate = CarbonImmutable::create(1900, 1, 31);
        $calculationDate = $this->getDayPillarCalculationDate($date);
        $diffDays = $baseDate->diffInDays($calculationDate);
        $index = ($diffDays % 60) + 1;

        return $this->splitIndex((int)$index);
    }

    public function getDayPillarCalculationDate(CarbonImmutable $date): CarbonImmutable
    {
        // TODO: 日界判定に使う時刻基準は LMT 補正後でよいか要確認。
        return $date->hour >= 23
            ? $date->addDay()->startOfDay()
            : $date->startOfDay();
    }

    public function getHourPillar(CarbonImmutable $date, int $dayStemId): array
    {
        $branchId = $this->resolveHourBranchId($date);

        $baseStemByDay = [
            1 => 1, 6 => 1, 2 => 3, 7 => 3, 3 => 5, 
            8 => 5, 4 => 7, 9 => 7, 5 => 9, 10 => 9,
        ];
        
        $startStemId = $baseStemByDay[$dayStemId] ?? 1;
        $stemId = ($startStemId + $branchId - 2) % 10 + 1;

        return ['stem_id' => $stemId, 'branch_id' => $branchId];
    }

    public function resolveHourBranchId(CarbonImmutable $date): int
    {
        // TODO: 時支判定に使う時刻基準は LMT 補正後でよいか要確認。
        return match (true) {
            $date->hour === 23 || $date->hour === 0 => 1, // 子: 23:00-00:59
            $date->hour < 3 => 2, // 丑: 01:00-02:59
            $date->hour < 5 => 3, // 寅: 03:00-04:59
            $date->hour < 7 => 4, // 卯: 05:00-06:59
            $date->hour < 9 => 5, // 辰: 07:00-08:59
            $date->hour < 11 => 6, // 巳: 09:00-10:59
            $date->hour < 13 => 7, // 午: 11:00-12:59
            $date->hour < 15 => 8, // 未: 13:00-14:59
            $date->hour < 17 => 9, // 申: 15:00-16:59
            $date->hour < 19 => 10, // 酉: 17:00-18:59
            $date->hour < 21 => 11, // 戌: 19:00-20:59
            default => 12, // 亥: 21:00-22:59
        };
    }

    private function splitIndex(int $index): array
    {
        $stemId = $index % 10 ?: 10;
        $branchId = $index % 12 ?: 12;
        return ['stem_id' => $stemId, 'branch_id' => $branchId];
    }
}
