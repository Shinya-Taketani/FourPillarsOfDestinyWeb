<?php

namespace Tests\Feature;

use Database\Seeders\SolarTermDefinitionSeeder;
use Database\Seeders\SolarTermEventSeeder;
use Database\Seeders\TaizanMasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalysisValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_single_analysis_api_accepts_valid_input(): void
    {
        $this->seedCalendarEvents();

        $response = $this->postJson('/api/analyze', $this->validPayload());

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('target_name', '検証者')
            ->assertJsonPath('data.schema_version', 1)
            ->assertJsonPath('data.status', 'calculated')
            ->assertJsonPath('data.calculation_metadata.calendar_policy.day_boundary', '23:00')
            ->assertJsonPath('data.calculation_metadata.adopted_solar_term_source_rank', 'S')
            ->assertJsonPath('data.calculation_metadata.solar_term_adopted', true)
            ->assertJsonCount(5, 'data.five_elements_scores')
            ->assertJsonStructure([
                'data' => [
                    'calculation_metadata',
                    'chart' => [
                        'year' => ['stem_id', 'branch_id', 'stem_name', 'branch_name', 'pillar'],
                        'month' => ['stem_id', 'branch_id', 'stem_name', 'branch_name', 'pillar'],
                        'day' => ['stem_id', 'branch_id', 'stem_name', 'branch_name', 'pillar'],
                        'hour' => ['stem_id', 'branch_id', 'stem_name', 'branch_name', 'pillar'],
                    ],
                    'hidden_stems',
                    'ten_gods',
                    'twelve_life_stages',
                    'five_element_strength' => [
                        'raw_scores',
                        'seasonal_adjusted_scores',
                        'normalized_scores',
                        'seasonal_status',
                        'basis',
                        'source_rank',
                        'source_note',
                    ],
                    'dayun',
                    'ryunen',
                    'interpretation',
                    'warnings',
                    'pillars',
                    'five_elements_scores',
                    'saiun',
                    'getsuun',
                    'appraisal',
                    'relation_items',
                ],
            ])
            ->assertJsonPath('data.judgement.strength.status', 'pending')
            ->assertJsonPath('data.judgement.strength.basis.five_element_strength.source_rank', 'PENDING')
            ->assertJsonPath('data.judgement.relations.status', 'pending')
            ->assertJsonPath('data.judgement.relations.source_rank', 'PENDING')
            ->assertJsonStructure([
                'data' => [
                    'relations' => ['status', 'items', 'source_rank', 'source_note'],
                    'judgement' => [
                        'relations' => ['status', 'items', 'source_rank', 'source_note'],
                    ],
                ],
            ])
            ->assertJsonPath('data.relations.status', 'pending')
            ->assertJsonPath('data.relations.source_rank', 'PENDING')
            ->assertJsonPath('data.judgement.pattern.status', 'pending')
            ->assertJsonPath('data.judgement.useful_god.status', 'pending')
            ->assertJsonPath('data.judgement.favorable_unfavorable.status', 'pending');

        json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);
    }

    public function test_single_analysis_api_accepts_existing_datetime_local_birthday_shape(): void
    {
        $this->seedCalendarEvents();

        $payload = $this->validPayload();
        $payload['birthday'] = '2026-03-05T12:00';
        unset($payload['birth_time']);

        $response = $this->postJson('/api/analyze', $payload);

        $response->assertOk()
            ->assertJsonPath('status', 'success');
    }

    public function test_single_analysis_api_rejects_invalid_input(): void
    {
        $cases = [
            'missing birthday' => ['birthday' => null],
            'invalid birthday format' => ['birthday' => '2026/03/05'],
            'missing birth time' => ['birth_time' => null],
            'invalid birth time format' => ['birth_time' => '25:99'],
            'invalid gender' => ['gender' => 'unknown'],
            'missing longitude' => ['longitude' => null],
            'longitude out of range' => ['longitude' => 151],
        ];

        foreach ($cases as $label => $override) {
            $payload = array_merge($this->validPayload(), $override);

            $this->postJson('/api/analyze', $payload)
                ->assertUnprocessable();
        }
    }

    public function test_single_analysis_api_does_not_fabricate_result_when_calendar_data_is_missing(): void
    {
        $this->seedCalendarEvents();

        $payload = $this->validPayload();
        $payload['birthday'] = '2025-03-05';

        $this->postJson('/api/analyze', $payload)
            ->assertUnprocessable()
            ->assertJsonPath('status', 'error')
            ->assertJsonMissingPath('data.schema_version');
    }

    public function test_1980_input_returns_safe_calendar_data_error(): void
    {
        $this->seedCalendarEvents();
        $payload = $this->validPayload();
        $payload['birthday'] = '1980-01-01';

        $this->postJson('/api/analyze', $payload)
            ->assertUnprocessable()
            ->assertJsonPath('status', 'error')
            ->assertJsonMissingPath('data');
    }

    private function validPayload(): array
    {
        return [
            'name' => '検証者',
            'birthday' => '2026-03-05',
            'birth_time' => '12:00',
            'gender' => 'male',
            'longitude' => 135.0,
            'target_datetime' => '2026-07-01T00:00',
        ];
    }

    private function seedCalendarEvents(): void
    {
        $this->seed(TaizanMasterSeeder::class);
        $this->seed(SolarTermDefinitionSeeder::class);
        $this->seed(SolarTermEventSeeder::class);
    }
}
