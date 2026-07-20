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

    public function test_api_reports_audit_rejected_and_missing_years_without_hiding_gaps(): void
    {
        $this->seed(TaizanMasterSeeder::class);
        $this->seed(SolarTermDefinitionSeeder::class);
        $this->seed(SolarTermEventSeeder::class);

        $this->getJson('/api/calendar-coverage')
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.birth_date.min_year', 1900)
            ->assertJsonPath('data.birth_date.max_year', 2100)
            ->assertJsonPath('data.internal_data.min_year', 1899)
            ->assertJsonPath('data.internal_data.max_year', 2101)
            ->assertJsonPath('data.expected_terms_per_year', 24)
            ->assertJsonPath('data.complete', false)
            ->assertJsonPath('data.missing_years', [1971, 1980]);

        DB::table('solar_term_events')
            ->where('year', 1950)
            ->where('solar_term_definition_id', DB::table('solar_term_definitions')->where('name', '春分')->value('id'))
            ->delete();

        $this->getJson('/api/calendar-coverage')
            ->assertOk()
            ->assertJsonPath('data.complete', false)
            ->assertJsonPath('data.missing_years', [1950, 1971, 1980]);

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
