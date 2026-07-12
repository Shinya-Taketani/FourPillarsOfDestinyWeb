<?php

namespace Tests\Feature;

use App\Services\DestinyCalculationService;
use App\Services\SexagenaryService;
use Carbon\CarbonImmutable;
use Database\Seeders\SolarTermDefinitionSeeder;
use Database\Seeders\SolarTermEventSeeder;
use Database\Seeders\TaizanMasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HourPillarBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_hour_branch_boundaries_are_resolved_explicitly(): void
    {
        $service = app(SexagenaryService::class);

        $cases = [
            ['2026-03-05 22:59:59', '亥'],
            ['2026-03-05 23:00:00', '子'],
            ['2026-03-05 23:59:59', '子'],
            ['2026-03-06 00:00:00', '子'],
            ['2026-03-06 00:59:59', '子'],
            ['2026-03-06 01:00:00', '丑'],
            ['2026-03-06 02:59:59', '丑'],
            ['2026-03-06 03:00:00', '寅'],
            ['2026-03-06 05:00:00', '卯'],
            ['2026-03-06 11:00:00', '午'],
            ['2026-03-06 21:00:00', '亥'],
        ];

        foreach ($cases as [$dateTime, $branchName]) {
            $this->assertSame(
                $this->branchId($branchName),
                $service->resolveHourBranchId(CarbonImmutable::parse($dateTime)),
                $dateTime,
            );
        }
    }

    public function test_day_boundary_and_hour_branch_are_consistent_after_2300(): void
    {
        $service = app(SexagenaryService::class);

        $cases = [
            ['2026-03-05 22:59:00', '2026-03-05 00:00:00', '亥'],
            ['2026-03-05 23:00:00', '2026-03-06 00:00:00', '子'],
            ['2026-03-05 23:30:00', '2026-03-06 00:00:00', '子'],
            ['2026-03-06 00:30:00', '2026-03-06 00:00:00', '子'],
            ['2026-03-06 01:00:00', '2026-03-06 00:00:00', '丑'],
        ];

        foreach ($cases as [$dateTime, $dayCalculationDate, $branchName]) {
            $date = CarbonImmutable::parse($dateTime);

            $this->assertSame($dayCalculationDate, $service->getDayPillarCalculationDate($date)->toDateTimeString());
            $this->assertSame($this->branchId($branchName), $service->resolveHourBranchId($date));
        }
    }

    public function test_destiny_calculation_uses_corrected_day_stem_for_hour_pillar_after_2300(): void
    {
        $this->seedCalendarEvents();

        $service = app(SexagenaryService::class);
        $date = CarbonImmutable::parse('2026-03-05 23:00:00');

        $dayPillar = $service->getDayPillar($date);
        $hourPillar = $service->getHourPillar($date, $dayPillar['stem_id']);
        $result = app(DestinyCalculationService::class)->analyze('2026-03-05T23:00', 135.0);

        $this->assertSame($this->pillarKanji($dayPillar), $result['pillars']['day']['kanji']);
        $this->assertSame($this->pillarKanji($hourPillar), $result['pillars']['hour']['kanji']);
        $this->assertSame($this->branchId('子'), $hourPillar['branch_id']);
    }

    private function seedCalendarEvents(): void
    {
        $this->seed(TaizanMasterSeeder::class);
        $this->seed(SolarTermDefinitionSeeder::class);
        $this->seed(SolarTermEventSeeder::class);
    }

    private function branchId(string $name): int
    {
        $branches = [
            '子' => 1, '丑' => 2, '寅' => 3, '卯' => 4, '辰' => 5, '巳' => 6,
            '午' => 7, '未' => 8, '申' => 9, '酉' => 10, '戌' => 11, '亥' => 12,
        ];

        return $branches[$name];
    }

    /**
     * @param array{stem_id: int, branch_id: int} $pillar
     */
    private function pillarKanji(array $pillar): string
    {
        $stems = [
            1 => '甲', 2 => '乙', 3 => '丙', 4 => '丁', 5 => '戊',
            6 => '己', 7 => '庚', 8 => '辛', 9 => '壬', 10 => '癸',
        ];
        $branches = [
            1 => '子', 2 => '丑', 3 => '寅', 4 => '卯', 5 => '辰', 6 => '巳',
            7 => '午', 8 => '未', 9 => '申', 10 => '酉', 11 => '戌', 12 => '亥',
        ];

        return $stems[$pillar['stem_id']] . $branches[$pillar['branch_id']];
    }
}
