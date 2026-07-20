<?php

namespace Tests\Feature;

use Database\Seeders\SolarTermDefinitionSeeder;
use Database\Seeders\SolarTermEventSeeder;
use Database\Seeders\TaizanMasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompatibilityValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_compatibility_api_accepts_valid_input(): void
    {
        $this->seedCalendarEvents();

        $response = $this->postJson('/api/analyze-compatibility', $this->validPayload());

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.schema_version', 1)
            ->assertJsonPath('data.status', 'calculated')
            ->assertJsonPath('data.person1_result.schema_version', 1)
            ->assertJsonPath('data.person2_result.schema_version', 1)
            ->assertJsonPath('data.relations.status', 'pending')
            ->assertJsonStructure([
                'data' => [
                    'person1_result',
                    'person2_result',
                    'compatibility',
                    'relations' => ['status', 'items', 'source_rank', 'source_note'],
                    'interpretation' => ['status', 'summary', 'sections', 'source_rank', 'source_note'],
                    'warnings',
                    'person1',
                    'person2',
                ],
            ]);

        json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);
    }

    public function test_compatibility_api_rejects_invalid_input(): void
    {
        $cases = [
            'missing person1 birthday' => ['person1.birthday' => null],
            'missing person2 birthday' => ['person2.birthday' => null],
            'person1 longitude out of range' => ['person1.longitude' => 151],
            'person2 invalid gender' => ['person2.gender' => 'unknown'],
            'missing person1' => ['person1' => null],
            'missing person2' => ['person2' => null],
        ];

        foreach ($cases as $override) {
            $payload = $this->validPayload();

            foreach ($override as $key => $value) {
                data_set($payload, $key, $value);
            }

            $this->postJson('/api/analyze-compatibility', $payload)
                ->assertUnprocessable();
        }
    }

    public function test_compatibility_birth_dates_are_limited_to_public_coverage(): void
    {
        foreach ([['person1.birthday', '1899-12-31'], ['person2.birthday', '2101-01-01']] as [$key, $birthday]) {
            $payload = $this->validPayload();
            data_set($payload, $key, $birthday);

            $this->postJson('/api/analyze-compatibility', $payload)
                ->assertUnprocessable()
                ->assertJsonValidationErrors($key);
        }
    }

    private function validPayload(): array
    {
        return [
            'person1' => [
                'name' => '自分',
                'birthday' => '2026-03-05',
                'birth_time' => '12:00',
                'gender' => 'male',
                'longitude' => 135.0,
            ],
            'person2' => [
                'name' => '相手',
                'birthday' => '2026-03-06',
                'birth_time' => '12:00',
                'gender' => 'female',
                'longitude' => 135.0,
            ],
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
