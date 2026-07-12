<?php

namespace Tests\Feature;

use App\Services\DestinyCalculationService;
use App\Services\LmtCalculatorService;
use App\Services\SexagenaryService;
use Carbon\CarbonImmutable;
use Database\Seeders\SolarTermDefinitionSeeder;
use Database\Seeders\SolarTermEventSeeder;
use Database\Seeders\TaizanMasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimeBasisTest extends TestCase
{
    use RefreshDatabase;

    public function test_lmt_correction_uses_jst_standard_longitude(): void
    {
        $service = app(LmtCalculatorService::class);
        $dateTime = CarbonImmutable::parse('2026-03-05 12:00:00');

        $this->assertSame('2026-03-05 12:00:00', $service->calculate($dateTime, 135.0)->toDateTimeString());
        $this->assertSame('2026-03-05 12:19:00', $service->calculate($dateTime, 139.75)->toDateTimeString());
        $this->assertSame('2026-03-05 11:40:00', $service->calculate($dateTime, 130.0)->toDateTimeString());
    }

    public function test_year_pillar_boundary_uses_jst_not_lmt(): void
    {
        $this->seedCalendarEvents();

        $westLongitude = 130.0;
        $eastLongitude = 139.75;

        $before = app(DestinyCalculationService::class)->analyze('2026-02-04T05:01', $eastLongitude);
        $at = app(DestinyCalculationService::class)->analyze('2026-02-04T05:02', $westLongitude);

        $this->assertSame('乙巳', $before['pillars']['year']['kanji']);
        $this->assertSame('丙午', $at['pillars']['year']['kanji']);
    }

    public function test_month_pillar_boundary_uses_jst_not_lmt(): void
    {
        $this->seedCalendarEvents();

        $westLongitude = 130.0;
        $eastLongitude = 139.75;

        $before = app(DestinyCalculationService::class)->analyze('2026-03-05T22:58', $eastLongitude);
        $at = app(DestinyCalculationService::class)->analyze('2026-03-05T22:59', $westLongitude);

        $this->assertSame('立春', $before['solar_term']);
        $this->assertSame('啓蟄', $at['solar_term']);
    }

    public function test_day_and_hour_pillars_use_lmt_in_current_design(): void
    {
        $this->seedCalendarEvents();

        $sexagenaryService = app(SexagenaryService::class);
        $birthDateTimeLmt = CarbonImmutable::parse('2026-03-05 23:04:00');
        $expectedDayPillar = $sexagenaryService->getDayPillar($birthDateTimeLmt);
        $expectedHourPillar = $sexagenaryService->getHourPillar($birthDateTimeLmt, $expectedDayPillar['stem_id']);

        $result = app(DestinyCalculationService::class)->analyze('2026-03-05T22:45', 139.75);

        $this->assertSame('2026-03-05 23:04:00', $result['lmt_datetime']);
        $this->assertSame($this->pillarKanji($expectedDayPillar), $result['pillars']['day']['kanji']);
        $this->assertSame($this->pillarKanji($expectedHourPillar), $result['pillars']['hour']['kanji']);
    }

    private function seedCalendarEvents(): void
    {
        $this->seed(TaizanMasterSeeder::class);
        $this->seed(SolarTermDefinitionSeeder::class);
        $this->seed(SolarTermEventSeeder::class);
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
