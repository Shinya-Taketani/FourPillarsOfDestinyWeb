<?php

namespace Tests\Feature;

use App\Services\SolarTermService;
use Carbon\CarbonImmutable;
use Database\Seeders\SolarTermDefinitionSeeder;
use Database\Seeders\SolarTermEventSeeder;
use Database\Seeders\TaizanMasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SolarTermEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_solar_term_definitions_seed_twenty_four_terms_and_twelve_month_boundaries(): void
    {
        $this->seed(TaizanMasterSeeder::class);
        $this->seed(SolarTermDefinitionSeeder::class);

        $this->assertSame(24, DB::table('solar_term_definitions')->count());
        $this->assertSame(12, DB::table('solar_term_definitions')->where('term_type', 'major_term')->count());
        $this->assertSame(12, DB::table('solar_term_definitions')->where('term_type', 'middle_term')->count());

        $names = DB::table('solar_term_definitions')
            ->where('is_month_boundary', true)
            ->orderBy('display_order')
            ->pluck('name')
            ->all();

        $this->assertSame(
            ['小寒', '立春', '啓蟄', '清明', '立夏', '芒種', '小暑', '立秋', '白露', '寒露', '立冬', '大雪'],
            $names,
        );
    }

    public function test_solar_term_events_seed_2026_adopted_solar_terms(): void
    {
        $this->seedCalendarEvents();

        $this->assertSame(
            24,
            DB::table('solar_term_events')
                ->where('year', 2026)
                ->where('adopted', true)
                ->where('source_rank', 'S2')
                ->count(),
        );
    }

    public function test_lichun_2026_can_be_loaded_from_adopted_event(): void
    {
        $this->seedCalendarEvents();

        $lichun = app(SolarTermService::class)->getLichunDateTime(2026);

        $this->assertSame('2026-02-04 05:02:00', $lichun?->format('Y-m-d H:i:s'));
        $this->assertSame('Asia/Tokyo', $lichun?->timezoneName);
    }

    public function test_adopted_event_lookup_ignores_non_adopted_rows(): void
    {
        $this->seedCalendarEvents();

        $definitionId = DB::table('solar_term_definitions')->where('name', '立春')->value('id');

        DB::table('solar_term_events')->insert([
            'solar_term_definition_id' => $definitionId,
            'year' => 2026,
            'started_at' => '2026-02-04 05:00:00',
            'timezone' => 'Asia/Tokyo',
            'calendar_system' => 'approximate',
            'source_title' => '検証用近似データ',
            'source_rank' => 'C',
            'adopted' => false,
            'note' => '正式採用不可',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $event = app(SolarTermService::class)->getAdoptedSolarTermEvent('立春', 2026);

        $this->assertSame('2026-02-04 05:02:00', $event?->started_at);
        $this->assertSame('S2', $event?->source_rank);
    }

    public function test_month_boundary_comparison_uses_adopted_events(): void
    {
        $this->seedCalendarEvents();

        $service = app(SolarTermService::class);

        $beforeLichun = $service->getLatestMonthBoundaryEvent(CarbonImmutable::parse('2026-02-04 05:01:00'));
        $atLichun = $service->getLatestMonthBoundaryEvent(CarbonImmutable::parse('2026-02-04 05:02:00'));
        $beforeKeichitsu = $service->getLatestMonthBoundaryEvent(CarbonImmutable::parse('2026-03-05 22:58:00'));
        $atKeichitsu = $service->getLatestMonthBoundaryEvent(CarbonImmutable::parse('2026-03-05 22:59:00'));

        $this->assertSame('小寒', $beforeLichun?->term_name);
        $this->assertSame('立春', $atLichun?->term_name);
        $this->assertSame('立春', $beforeKeichitsu?->term_name);
        $this->assertSame('啓蟄', $atKeichitsu?->term_name);
    }

    public function test_missing_year_is_not_approximated(): void
    {
        $this->seedCalendarEvents();

        $service = app(SolarTermService::class);

        $this->assertNull($service->getAdoptedSolarTermEvent('立春', 2102));
        $this->assertNull($service->getLichunDateTime(2102));
        $this->assertCount(0, $service->getMonthBoundaryEvents(2102));
    }

    private function seedCalendarEvents(): void
    {
        $this->seed(TaizanMasterSeeder::class);
        $this->seed(SolarTermDefinitionSeeder::class);
        $this->seed(SolarTermEventSeeder::class);
    }
}
