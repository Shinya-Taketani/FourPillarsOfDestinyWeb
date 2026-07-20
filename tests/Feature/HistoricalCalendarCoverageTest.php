<?php

namespace Tests\Feature;

use App\Services\SexagenaryService;
use App\Services\SolarTermService;
use Carbon\CarbonImmutable;
use Database\Seeders\SolarTermDefinitionSeeder;
use Database\Seeders\SolarTermEventSeeder;
use Database\Seeders\TaizanMasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HistoricalCalendarCoverageTest extends TestCase
{
    use RefreshDatabase;

    public function test_1971_single_appraisal_uses_the_annual_official_dataset(): void
    {
        $this->seedCalendarEvents();

        $this->postJson('/api/analyze', [
            'name' => '1971年検証',
            'birthday' => '1971-02-01',
            'birth_time' => '07:45',
            'gender' => 'male',
            'longitude' => 135.76,
            'target_datetime' => '2026-07-01T00:00',
        ])->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.calculation_metadata.adopted_solar_term_source_rank', 'S1')
            ->assertJsonPath('data.calculation_metadata.adopted_solar_term_source.verification_status', 'verified');

        $this->assertAnnualAndAuditEvents(1971, '1971-02-04 20:26:00', '1971-02-04 20:25:00');
    }

    public function test_1980_single_and_compatibility_appraisals_succeed(): void
    {
        $this->seedCalendarEvents();

        $person = [
            'name' => '検証者',
            'birthday' => '1980-01-01T09:00',
            'gender' => 'male',
            'longitude' => 135.76,
        ];

        $this->postJson('/api/analyze', [
            ...$person,
            'birth_time' => '09:00',
            'birthday' => '1980-01-01',
            'target_datetime' => '2026-07-01T00:00',
        ])->assertOk()
            ->assertJsonPath('status', 'success');

        $this->postJson('/api/analyze-compatibility', [
            'person1' => $person,
            'person2' => [...$person, 'name' => '相手', 'gender' => 'female'],
            'target_datetime' => '2026-07-01T00:00',
        ])->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure(['data' => ['person1_result', 'person2_result', 'person1', 'person2']]);

        $this->assertAnnualAndAuditEvents(1980, '1980-02-05 01:10:00', '1980-02-05 01:09:00');
    }

    public function test_2026_year_pillar_boundary_remains_available_at_the_verified_minute(): void
    {
        $this->seedCalendarEvents();

        $event = app(SolarTermService::class)->getAdoptedSolarTermEvent('立春', 2026);

        $this->assertNotNull($event);
        $this->assertSame('2026-02-04 05:02:00', $event->started_at);
        $this->assertSame('S1', $event->source_rank);
        $this->assertSame('verified', $event->verification_status);
    }

    public function test_1971_and_1980_year_pillars_switch_at_the_annual_official_boundary(): void
    {
        $this->seedCalendarEvents();
        $service = app(SexagenaryService::class);

        foreach ([
            1971 => ['20:25:00', '20:26:00', '20:27:00'],
            1980 => ['01:09:00', '01:10:00', '01:11:00'],
        ] as $year => [$beforeTime, $atTime, $afterTime]) {
            $date = $year === 1971 ? '1971-02-04' : '1980-02-05';
            $this->assertSame($year - 1, $service->getPillarYear(CarbonImmutable::parse("{$date} {$beforeTime}", 'Asia/Tokyo')));
            $this->assertSame($year, $service->getPillarYear(CarbonImmutable::parse("{$date} {$atTime}", 'Asia/Tokyo')));
            $this->assertSame($year, $service->getPillarYear(CarbonImmutable::parse("{$date} {$afterTime}", 'Asia/Tokyo')));
        }
    }

    public function test_public_boundaries_and_internal_buffer_events_are_available(): void
    {
        $this->seedCalendarEvents();
        $solarTerms = app(SolarTermService::class);

        $this->postJson('/api/analyze', [
            'name' => '下限', 'birthday' => '1900-01-01', 'birth_time' => '00:00',
            'gender' => 'male', 'longitude' => 135.76, 'target_datetime' => '1900-01-01T00:00',
        ])->assertOk();

        $this->postJson('/api/analyze', [
            'name' => '上限', 'birthday' => '2100-12-31', 'birth_time' => '23:59',
            'gender' => 'female', 'longitude' => 135.76, 'target_datetime' => '2100-12-31T23:59',
        ])->assertOk();

        $this->assertSame('1899-12-07 16:05:00', $solarTerms->getAdoptedSolarTermEvent('大雪', 1899)?->started_at);
        $this->assertSame('2101-01-05 22:08:00', $solarTerms->getAdoptedSolarTermEvent('小寒', 2101)?->started_at);
    }

    private function seedCalendarEvents(): void
    {
        $this->seed(TaizanMasterSeeder::class);
        $this->seed(SolarTermDefinitionSeeder::class);
        $this->seed(SolarTermEventSeeder::class);
    }

    private function assertAnnualAndAuditEvents(int $year, string $annualLichun, string $longTermLichun): void
    {
        $definitionId = DB::table('solar_term_definitions')->where('name', '立春')->value('id');
        $annualEvent = DB::table('solar_term_events')
            ->where('year', $year)
            ->where('solar_term_definition_id', $definitionId)
            ->where('source_rank', 'S1')
            ->first();
        $longTermEvent = DB::table('solar_term_events')
            ->where('year', $year)
            ->where('solar_term_definition_id', $definitionId)
            ->where('source_rank', 'S2')
            ->first();

        $this->assertNotNull($annualEvent);
        $this->assertSame($annualLichun, $annualEvent->started_at);
        $this->assertTrue((bool) $annualEvent->adopted);
        $this->assertSame('verified', $annualEvent->verification_status);
        $this->assertSame('Asia/Tokyo', $annualEvent->timezone);
        $this->assertSame('minute', $annualEvent->precision_level);

        $this->assertNotNull($longTermEvent);
        $this->assertSame($longTermLichun, $longTermEvent->started_at);
        $this->assertFalse((bool) $longTermEvent->adopted);
        $this->assertSame('superseded_discrepant', $longTermEvent->verification_status);
        $this->assertStringContainsString('1分差', (string) $longTermEvent->note);
    }
}
