<?php

namespace Tests\Feature;

use App\Exceptions\CalendarDataUnavailableException;
use App\Services\SexagenaryService;
use Carbon\CarbonImmutable;
use Database\Seeders\SolarTermDefinitionSeeder;
use Database\Seeders\SolarTermEventSeeder;
use Database\Seeders\TaizanMasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PillarBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_year_pillar_switches_at_2026_lichun(): void
    {
        $this->seedCalendarEvents();

        $service = app(SexagenaryService::class);

        $this->assertSame(2025, $service->getPillarYear(CarbonImmutable::parse('2026-02-04 05:01:00')));
        $this->assertSame(2026, $service->getPillarYear(CarbonImmutable::parse('2026-02-04 05:02:00')));
        $this->assertSame(['stem_id' => 2, 'branch_id' => 6], $service->getYearPillar(CarbonImmutable::parse('2026-02-04 05:01:00')));
        $this->assertSame(['stem_id' => 3, 'branch_id' => 7], $service->getYearPillar(CarbonImmutable::parse('2026-02-04 05:02:00')));
    }

    public function test_month_pillar_switches_at_2026_keichitsu(): void
    {
        $this->seedCalendarEvents();

        $service = app(SexagenaryService::class);
        $yearStemId = $service->getYearPillar(CarbonImmutable::parse('2026-03-05 22:59:00'))['stem_id'];

        $before = $service->getMonthPillar(CarbonImmutable::parse('2026-03-05 22:58:00'), $yearStemId);
        $at = $service->getMonthPillar(CarbonImmutable::parse('2026-03-05 22:59:00'), $yearStemId);

        $this->assertSame($this->branchId('寅'), $before['branch_id']);
        $this->assertSame('立春', $before['solar_term_name']);
        $this->assertSame($this->branchId('卯'), $at['branch_id']);
        $this->assertSame('啓蟄', $at['solar_term_name']);
    }

    public function test_month_pillar_switches_at_2026_hakuro(): void
    {
        $this->seedCalendarEvents();

        $service = app(SexagenaryService::class);
        $yearStemId = $service->getYearPillar(CarbonImmutable::parse('2026-09-07 23:41:00'))['stem_id'];

        $before = $service->getMonthPillar(CarbonImmutable::parse('2026-09-07 23:40:00'), $yearStemId);
        $at = $service->getMonthPillar(CarbonImmutable::parse('2026-09-07 23:41:00'), $yearStemId);

        $this->assertSame($this->branchId('申'), $before['branch_id']);
        $this->assertSame('立秋', $before['solar_term_name']);
        $this->assertSame($this->branchId('酉'), $at['branch_id']);
        $this->assertSame('白露', $at['solar_term_name']);
    }

    public function test_unregistered_year_is_not_approximated(): void
    {
        $this->seedCalendarEvents();

        $this->expectException(CalendarDataUnavailableException::class);
        $this->expectExceptionMessage('指定年の採用済み立春データが未登録です');

        app(SexagenaryService::class)->getYearPillar(CarbonImmutable::parse('2027-02-04 05:02:00'));
    }

    private function seedCalendarEvents(): void
    {
        $this->seed(TaizanMasterSeeder::class);
        $this->seed(SolarTermDefinitionSeeder::class);
        $this->seed(SolarTermEventSeeder::class);
    }

    private function branchId(string $name): int
    {
        return (int) DB::table('master_branches')->where('name', $name)->value('id');
    }
}
