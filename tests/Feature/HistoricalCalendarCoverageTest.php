<?php

namespace Tests\Feature;

use App\Exceptions\CalendarDataUnavailableException;
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

    public function test_1971_single_appraisal_is_rejected_while_annual_audit_diff_is_unresolved(): void
    {
        $this->seedCalendarEvents();

        $this->postJson('/api/analyze', [
            'name' => '1971年検証',
            'birthday' => '1971-02-01',
            'birth_time' => '07:45',
            'gender' => 'male',
            'longitude' => 135.76,
            'target_datetime' => '2026-07-01T00:00',
        ])->assertUnprocessable()
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', '指定年の採用済み立春データが未登録です: 1971年');

        $this->assertAuditRejectedYear(1971, '1971-02-04 20:25:00', '1971-02-04 20:26:00');
    }

    public function test_1980_compatibility_is_rejected_while_annual_audit_diff_is_unresolved(): void
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
        ])->assertUnprocessable()
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', '指定年の採用済み立春データが未登録です: 1980年');

        $this->assertAuditRejectedYear(1980, '1980-02-05 01:09:00', '1980-02-05 01:10:00');
    }

    public function test_2026_year_pillar_boundary_remains_available_at_the_verified_minute(): void
    {
        $this->seedCalendarEvents();

        $event = app(SolarTermService::class)->getAdoptedSolarTermEvent('立春', 2026);

        $this->assertNotNull($event);
        $this->assertSame('2026-02-04 05:02:00', $event->started_at);
        $this->assertSame('verified', $event->verification_status);
    }

    public function test_rejected_1971_and_1980_boundaries_do_not_silently_select_a_pillar(): void
    {
        $this->seedCalendarEvents();
        $service = app(SexagenaryService::class);

        foreach ([
            '1971-02-04 20:25:00', '1971-02-04 20:26:00', '1971-02-04 20:27:00',
            '1980-02-05 01:09:00', '1980-02-05 01:10:00', '1980-02-05 01:11:00',
        ] as $dateTime) {
            try {
                $service->getPillarYear(CarbonImmutable::parse($dateTime, 'Asia/Tokyo'));
                $this->fail("採用保留年の境界を計算してはいけません: {$dateTime}");
            } catch (CalendarDataUnavailableException) {
                $this->addToAssertionCount(1);
            }
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

    private function assertAuditRejectedYear(int $year, string $actualLichun, string $expectedLichun): void
    {
        $definitionId = DB::table('solar_term_definitions')->where('name', '立春')->value('id');
        $event = DB::table('solar_term_events')
            ->where('year', $year)
            ->where('solar_term_definition_id', $definitionId)
            ->first();

        $this->assertNotNull($event);
        $this->assertSame($actualLichun, $event->started_at);
        $this->assertFalse((bool) $event->adopted);
        $this->assertSame('rejected', $event->verification_status);
        $this->assertStringContainsString($expectedLichun, (string) $event->note);
    }
}
