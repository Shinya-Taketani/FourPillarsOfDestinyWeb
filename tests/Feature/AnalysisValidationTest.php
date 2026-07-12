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
            ->assertJsonPath('target_name', '検証者');
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
