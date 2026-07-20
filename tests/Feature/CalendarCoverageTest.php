<?php

namespace Tests\Feature;

use Database\Seeders\SolarTermDefinitionSeeder;
use Database\Seeders\SolarTermEventSeeder;
use Database\Seeders\TaizanMasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CalendarCoverageTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_reports_complete_coverage_and_detects_an_intermediate_missing_year(): void
    {
        $this->seed(TaizanMasterSeeder::class);
        $this->seed(SolarTermDefinitionSeeder::class);
        $this->seed(SolarTermEventSeeder::class);

        $this->getJson('/api/calendar-coverage')
            ->assertOk()
            ->assertJsonPath('birth_date.min_year', 1900)
            ->assertJsonPath('birth_date.max_year', 2100)
            ->assertJsonPath('internal_data.min_year', 1899)
            ->assertJsonPath('internal_data.max_year', 2101)
            ->assertJsonPath('complete', true)
            ->assertJsonCount(0, 'missing_years');

        DB::table('solar_term_events')
            ->where('year', 1950)
            ->where('solar_term_definition_id', DB::table('solar_term_definitions')->where('name', '春分')->value('id'))
            ->delete();

        $this->getJson('/api/calendar-coverage')
            ->assertOk()
            ->assertJsonPath('complete', false)
            ->assertJsonFragment(['missing_years' => [1950]]);

        $this->postJson('/api/analyze', [
            'name' => '欠損年確認',
            'birthday' => '1950-03-01',
            'birth_time' => '12:00',
            'gender' => 'male',
            'longitude' => 135.76,
            'target_datetime' => '2026-07-01T00:00',
        ])->assertUnprocessable()
            ->assertJsonPath('status', 'error');
    }
}
