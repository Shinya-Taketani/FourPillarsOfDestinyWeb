<?php

namespace Tests\Feature;

use App\Services\SexagenaryService;
use App\Services\SolarTermService;
use Carbon\CarbonImmutable;
use Database\Seeders\SolarTermDefinitionSeeder;
use Database\Seeders\SolarTermEventSeeder;
use Database\Seeders\TaizanMasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HistoricalCalendarCoverageTest extends TestCase
{
    use RefreshDatabase;

    public function test_1971_single_appraisal_uses_adopted_naoj_events(): void
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
            ->assertJsonPath('data.solar_term', '小寒')
            ->assertJsonPath('data.calculation_metadata.adopted_solar_term_source_rank', 'S')
            ->assertJsonPath('data.calculation_metadata.solar_term_adopted', true)
            ->assertJsonPath(
                'data.calculation_metadata.adopted_solar_term_source.url',
                'https://eco.mtk.nao.ac.jp/cgi-bin/koyomi/cande/phenomena_sy.cgi?year=1971',
            )
            ->assertJsonStructure(['data' => ['dayun' => ['basis_term']]]);
    }

    public function test_1980_compatibility_generates_both_destinies(): void
    {
        $this->seedCalendarEvents();

        $person = [
            'name' => '検証者',
            'birthday' => '1980-01-01T09:00',
            'gender' => 'male',
            'longitude' => 135.76,
        ];

        $this->postJson('/api/analyze-compatibility', [
            'person1' => $person,
            'person2' => [...$person, 'name' => '相手', 'gender' => 'female'],
            'target_datetime' => '2026-07-01T00:00',
        ])->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.person1_result.calculation_metadata.adopted_solar_term_source_rank', 'S')
            ->assertJsonPath('data.person2_result.calculation_metadata.adopted_solar_term_source_rank', 'S')
            ->assertJsonStructure(['data' => ['person1_result', 'person2_result', 'compatibility', 'relations']]);
    }

    public function test_1971_and_1980_year_pillars_switch_at_naoj_lichun_minute(): void
    {
        $this->seedCalendarEvents();
        $service = app(SexagenaryService::class);

        foreach ([
            [1971, '1971-02-04 20:24:00', '1971-02-04 20:25:00'],
            [1980, '1980-02-05 01:08:00', '1980-02-05 01:09:00'],
        ] as [$year, $before, $at]) {
            $this->assertSame($year - 1, $service->getPillarYear(CarbonImmutable::parse($before, 'Asia/Tokyo')));
            $this->assertSame($year, $service->getPillarYear(CarbonImmutable::parse($at, 'Asia/Tokyo')));
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
}
