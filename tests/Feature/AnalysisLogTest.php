<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\SolarTermDefinitionSeeder;
use Database\Seeders\SolarTermEventSeeder;
use Database\Seeders\TaizanMasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AnalysisLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_single_analysis_saves_minimal_analysis_log(): void
    {
        $this->seedCalendarEvents();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/analyze', $this->validPayload())
            ->assertOk();

        $this->assertDatabaseHas('analysis_targets', [
            'user_id' => $user->id,
            'name' => '検証者',
            'gender' => 1,
        ]);
        $this->assertDatabaseCount('analysis_logs', 1);

        $chartData = json_decode(DB::table('analysis_logs')->value('chart_data'), true);

        $this->assertSame(1, $chartData['schema_version']);
        $this->assertArrayHasKey('chart', $chartData);
        $this->assertArrayHasKey('pillars', $chartData);
        $this->assertArrayHasKey('five_elements_scores', $chartData);
        $this->assertArrayHasKey('warnings', $chartData);
        $this->assertArrayNotHasKey('name', $chartData);
        $this->assertArrayNotHasKey('birth_datetime', $chartData);
        $this->assertArrayNotHasKey('longitude', $chartData);
        $this->assertArrayNotHasKey('input', $chartData);
        $this->assertArrayNotHasKey('calculation_metadata', $chartData);
    }

    public function test_guest_single_analysis_does_not_create_analysis_log(): void
    {
        $this->seedCalendarEvents();

        $this->postJson('/api/analyze', $this->validPayload())
            ->assertOk();

        $this->assertDatabaseCount('analysis_targets', 0);
        $this->assertDatabaseCount('analysis_logs', 0);
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
