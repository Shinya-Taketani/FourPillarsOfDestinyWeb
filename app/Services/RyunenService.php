<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\CalendarDataUnavailableException;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

readonly class RyunenService
{
    public function __construct(
        private SolarTermService $solarTermService,
        private StarCalculationService $starService,
    ) {}

    public function getRyunenPillarByYear(int $year, ?int $dayStemId = null): array
    {
        $index = ($year - 3) % 60;
        if ($index <= 0) {
            $index += 60;
        }

        $stemId = $index % 10 ?: 10;
        $branchId = $index % 12 ?: 12;
        $stemName = DB::table('master_stems')->where('id', $stemId)->value('name');
        $branchName = DB::table('master_branches')->where('id', $branchId)->value('name');

        return [
            'year' => $year,
            'stem_id' => $stemId,
            'branch_id' => $branchId,
            'stem_name' => $stemName,
            'branch_name' => $branchName,
            'pillar' => ($stemName ?? '') . ($branchName ?? ''),
            'kanji' => ($stemName ?? '') . ($branchName ?? ''),
            'ten_god' => $dayStemId === null ? null : $this->starService->getTenGod($dayStemId, $stemId),
            'twelve_life_stage' => $dayStemId === null ? null : $this->starService->getTwelveLifeStage($dayStemId, $branchId),
        ];
    }

    public function getRyunenPillarByDateTime(CarbonImmutable $dateTime, ?int $dayStemId = null): array
    {
        return $this->getRyunenPillarByYear($this->resolveRyunenYear($dateTime), $dayStemId);
    }

    public function resolveRyunenYear(CarbonImmutable $dateTime): int
    {
        $lichun = $this->solarTermService->getLichunDateTime($dateTime->year);

        if ($lichun === null) {
            throw CalendarDataUnavailableException::forLichun($dateTime->year);
        }

        $localDateTime = CarbonImmutable::parse($dateTime->toDateTimeString(), $lichun->timezoneName);

        return $localDateTime->lt($lichun) ? $dateTime->year - 1 : $dateTime->year;
    }

    public function calculateAgeAtTargetDate(CarbonImmutable $birthDateTime, CarbonImmutable $targetDateTime): float
    {
        return round($birthDateTime->floatDiffInYears($targetDateTime), 4);
    }

    public function findActiveDayunByAge(array $dayunCycles, float $age): ?array
    {
        foreach ($dayunCycles as $cycle) {
            $startAge = (float)($cycle['start_age_years'] ?? $cycle['age'] ?? 0);
            $endAge = (float)($cycle['end_age_years'] ?? ($startAge + 10));

            if ($age >= $startAge && $age < $endAge) {
                return $cycle;
            }
        }

        return null;
    }

    public function getRyunenWithActiveDayun(
        CarbonImmutable $birthDateTime,
        CarbonImmutable $targetDateTime,
        array $dayunCycles,
        ?int $dayStemId = null,
    ): array {
        $age = $this->calculateAgeAtTargetDate($birthDateTime, $targetDateTime);
        $ryunenYear = $this->resolveRyunenYear($targetDateTime);

        return [
            'target_year' => $targetDateTime->year,
            'target_datetime' => $targetDateTime->toIso8601String(),
            'ryunen_year' => $ryunenYear,
            'age_at_target' => $age,
            'ryunen_pillar' => $this->getRyunenPillarByYear($ryunenYear, $dayStemId),
            'active_dayun' => $this->findActiveDayunByAge($dayunCycles, $age),
            // TODO: 流年通変星と大運の吉凶評価接続は次回以降。
            'calculation_note' => '流年は adopted=true の立春基準。吉凶判断は未実装。',
        ];
    }
}
