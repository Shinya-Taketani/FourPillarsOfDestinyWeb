<?php

namespace Tests\Feature;

use App\Exceptions\CalendarDataUnavailableException;
use App\Services\DestinyCalculationService;
use App\Services\RyunenService;
use App\Services\SexagenaryService;
use Carbon\CarbonImmutable;
use Database\Seeders\SolarTermDefinitionSeeder;
use Database\Seeders\SolarTermEventSeeder;
use Database\Seeders\TaizanMasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RyunenCalculationTest extends TestCase
{
    use RefreshDatabase;

    public function test_ryunen_year_switches_at_lichun_boundary(): void
    {
        $this->seedCalendarEvents();

        $service = app(RyunenService::class);

        $this->assertSame(2025, $service->resolveRyunenYear(CarbonImmutable::parse('2026-02-04 05:01:00', 'Asia/Tokyo')));
        $this->assertSame(2026, $service->resolveRyunenYear(CarbonImmutable::parse('2026-02-04 05:02:00', 'Asia/Tokyo')));
        $this->assertSame(2026, $service->resolveRyunenYear(CarbonImmutable::parse('2026-02-04 05:03:00', 'Asia/Tokyo')));
    }

    public function test_ryunen_pillar_by_year_returns_structured_pillar(): void
    {
        $this->seedCalendarEvents();

        $service = app(RyunenService::class);
        $sexagenary = app(SexagenaryService::class);
        $pillar = $service->getRyunenPillarByYear(2026, 1);
        $expected = $sexagenary->getYearPillar(CarbonImmutable::parse('2026-02-04 05:02:00'));

        $this->assertSame($expected['stem_id'], $pillar['stem_id']);
        $this->assertSame($expected['branch_id'], $pillar['branch_id']);
        $this->assertSame('丙', $pillar['stem_name']);
        $this->assertSame('午', $pillar['branch_name']);
        $this->assertSame('丙午', $pillar['pillar']);
        $this->assertSame('丙午', $pillar['kanji']);
        $this->assertNotNull($pillar['ten_god']);
        $this->assertNotNull($pillar['twelve_life_stage']);
    }

    public function test_active_dayun_can_be_found_by_age(): void
    {
        $service = app(RyunenService::class);
        $cycles = [
            ['index' => 1, 'pillar' => '己卯', 'start_age_years' => 2.5, 'end_age_years' => 12.5],
            ['index' => 2, 'pillar' => '庚辰', 'start_age_years' => 12.5, 'end_age_years' => 22.5],
        ];

        $this->assertSame(1, $service->findActiveDayunByAge($cycles, 2.5)['index']);
        $this->assertSame(1, $service->findActiveDayunByAge($cycles, 12.499)['index']);
        $this->assertSame(2, $service->findActiveDayunByAge($cycles, 12.5)['index']);
        $this->assertNull($service->findActiveDayunByAge($cycles, 22.5));
    }

    public function test_ryunen_with_active_dayun_returns_structured_result(): void
    {
        $this->seedCalendarEvents();

        $service = app(RyunenService::class);
        $birth = CarbonImmutable::parse('2020-02-04 05:02:00');
        $target = CarbonImmutable::parse('2026-02-04 05:02:00');
        $cycles = [
            ['index' => 1, 'pillar' => '己卯', 'start_age_years' => 1.0, 'end_age_years' => 11.0],
            ['index' => 2, 'pillar' => '庚辰', 'start_age_years' => 11.0, 'end_age_years' => 21.0],
        ];

        $result = $service->getRyunenWithActiveDayun($birth, $target, $cycles, 1);

        $this->assertSame(2026, $result['target_year']);
        $this->assertSame(2026, $result['ryunen_year']);
        $this->assertSame('丙午', $result['ryunen_pillar']['pillar']);
        $this->assertSame(1, $result['active_dayun']['index']);
        $this->assertArrayHasKey('age_at_target', $result);
    }

    public function test_missing_lichun_data_is_not_approximated(): void
    {
        $this->seedCalendarEvents();
        DB::table('solar_term_events')->where('year', 2027)->delete();

        $this->expectException(CalendarDataUnavailableException::class);

        app(RyunenService::class)->resolveRyunenYear(CarbonImmutable::parse('2027-02-04 05:02:00'));
    }

    public function test_destiny_calculation_uses_target_datetime_without_2026_fixed_saiun(): void
    {
        $this->seedCalendarEvents();

        $result = app(DestinyCalculationService::class)->analyze(
            '2026-03-05T12:00',
            135.0,
            'male',
            '2026-02-04T05:02:00',
        );

        $this->assertSame(2026, $result['ryunen']['ryunen_year']);
        $this->assertSame(2026, $result['saiun']['year']);

        DB::table('solar_term_events')->where('year', 2027)->delete();

        $this->expectException(CalendarDataUnavailableException::class);

        app(DestinyCalculationService::class)->analyze(
            '2026-03-05T12:00',
            135.0,
            'male',
            '2027-02-04T05:02:00',
        );
    }

    private function seedCalendarEvents(): void
    {
        $this->seed(TaizanMasterSeeder::class);
        $this->seed(SolarTermDefinitionSeeder::class);
        $this->seed(SolarTermEventSeeder::class);
    }
}
