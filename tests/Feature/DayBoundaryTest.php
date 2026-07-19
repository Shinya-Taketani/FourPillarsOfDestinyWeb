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

class DayBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_day_pillar_uses_2300_day_boundary(): void
    {
        $service = app(SexagenaryService::class);

        $before = CarbonImmutable::parse('2026-03-05 22:59:00');
        $atBoundary = CarbonImmutable::parse('2026-03-05 23:00:00');
        $afterBoundary = CarbonImmutable::parse('2026-03-05 23:30:00');
        $nextMidnight = CarbonImmutable::parse('2026-03-06 00:00:00');

        $this->assertSame('2026-03-05 00:00:00', $service->getDayPillarCalculationDate($before)->toDateTimeString());
        $this->assertSame('2026-03-06 00:00:00', $service->getDayPillarCalculationDate($atBoundary)->toDateTimeString());
        $this->assertSame('2026-03-06 00:00:00', $service->getDayPillarCalculationDate($afterBoundary)->toDateTimeString());

        $this->assertSame($service->getDayPillar($nextMidnight), $service->getDayPillar($atBoundary));
        $this->assertSame($service->getDayPillar($nextMidnight), $service->getDayPillar($afterBoundary));
        $this->assertNotSame($service->getDayPillar($before), $service->getDayPillar($atBoundary));
    }

    public function test_hour_pillar_uses_day_stem_after_2300_day_boundary(): void
    {
        $service = app(SexagenaryService::class);
        $atBoundary = CarbonImmutable::parse('2026-03-05 23:00:00');

        $correctedDayPillar = $service->getDayPillar($atBoundary);
        $previousDayPillar = $service->getDayPillar(CarbonImmutable::parse('2026-03-05 22:59:00'));

        $hourPillar = $service->getHourPillar($atBoundary, $correctedDayPillar['stem_id']);
        $hourPillarWithPreviousDayStem = $service->getHourPillar($atBoundary, $previousDayPillar['stem_id']);

        $this->assertSame($service->getHourPillar($atBoundary, $correctedDayPillar['stem_id']), $hourPillar);
        $this->assertNotSame($hourPillarWithPreviousDayStem, $hourPillar);
    }

    public function test_destiny_calculation_uses_day_boundary_for_day_and_hour_pillars(): void
    {
        $this->seedCalendarEvents();

        $service = app(SexagenaryService::class);
        $result = app(DestinyCalculationService::class)->analyze('2026-03-05T23:00', 135.0);

        $correctedDayPillar = $service->getDayPillar(CarbonImmutable::parse('2026-03-05 23:00:00'));
        $expectedHourPillar = $service->getHourPillar(
            CarbonImmutable::parse('2026-03-05 23:00:00'),
            $correctedDayPillar['stem_id'],
        );

        $this->assertSame($this->pillarKanji($correctedDayPillar), $result['pillars']['day']['kanji']);
        $this->assertSame($this->pillarKanji($expectedHourPillar), $result['pillars']['hour']['kanji']);
    }

    private function seedCalendarEvents(): void
    {
        $this->seed(TaizanMasterSeeder::class);
        $this->seed(SolarTermDefinitionSeeder::class);
        $this->seed(SolarTermEventSeeder::class);
    }

    /**
     * @param  array{stem_id: int, branch_id: int}  $pillar
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

        return $stems[$pillar['stem_id']].$branches[$pillar['branch_id']];
    }
}
