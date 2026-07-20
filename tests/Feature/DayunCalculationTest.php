<?php

namespace Tests\Feature;

use App\Exceptions\CalendarDataUnavailableException;
use App\Services\DayunService;
use App\Services\SolarTermService;
use Carbon\CarbonImmutable;
use Database\Seeders\SolarTermDefinitionSeeder;
use Database\Seeders\SolarTermEventSeeder;
use Database\Seeders\TaizanMasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DayunCalculationTest extends TestCase
{
    use RefreshDatabase;

    public function test_dayun_direction_is_determined_by_year_stem_yin_yang_and_gender(): void
    {
        $service = app(DayunService::class);

        $this->assertSame('forward', $service->determineDirection(1, 'male'));
        $this->assertSame('backward', $service->determineDirection(2, 'male'));
        $this->assertSame('backward', $service->determineDirection(1, 'female'));
        $this->assertSame('forward', $service->determineDirection(2, 'female'));
        $this->assertSame('forward', $service->determineDirection(1, 1));
        $this->assertSame('forward', $service->determineDirection(2, 2));
    }

    public function test_next_and_previous_month_boundary_events_use_adopted_solar_term_events(): void
    {
        $this->seedCalendarEvents();

        $service = app(SolarTermService::class);

        $this->assertSame(
            '啓蟄',
            $service->getNextMonthBoundaryEvent(CarbonImmutable::parse('2026-03-01 00:00:00'))?->term_name,
        );
        $this->assertSame(
            '啓蟄',
            $service->getPreviousMonthBoundaryEvent(CarbonImmutable::parse('2026-03-10 00:00:00'))?->term_name,
        );
        $this->assertSame(
            '立春',
            $service->getNextMonthBoundaryEvent(CarbonImmutable::parse('2026-02-04 05:01:00'))?->term_name,
        );
        $this->assertSame(
            '立春',
            $service->getPreviousMonthBoundaryEvent(CarbonImmutable::parse('2026-02-04 05:02:00'))?->term_name,
        );
    }

    public function test_start_age_is_calculated_with_three_days_as_one_year(): void
    {
        $service = app(DayunService::class);
        $birthDateTime = CarbonImmutable::parse('2026-03-01 00:00:00');

        $this->assertSame(
            ['start_age_years_decimal' => 1.0, 'start_age_years' => 1, 'start_age_months' => 0],
            $service->calculateStartAge($birthDateTime, $birthDateTime->addDays(3)),
        );
        $this->assertSame(
            ['start_age_years_decimal' => 2.0, 'start_age_years' => 2, 'start_age_months' => 0],
            $service->calculateStartAge($birthDateTime, $birthDateTime->addDays(6)),
        );
        $this->assertSame(
            ['start_age_years_decimal' => 0.5, 'start_age_years' => 0, 'start_age_months' => 6],
            $service->calculateStartAge($birthDateTime, $birthDateTime->addHours(36)),
        );
    }

    public function test_dayun_cycles_start_from_next_or_previous_month_pillar(): void
    {
        $this->seedCalendarEvents();

        $service = app(DayunService::class);
        $monthPillar = ['stem_id' => 5, 'branch_id' => 3];

        $forward = $service->calculate(
            ['stem_id' => 1, 'branch_id' => 1],
            $monthPillar,
            1,
            CarbonImmutable::parse('2026-03-01 00:00:00'),
            'male',
        );
        $backward = $service->calculate(
            ['stem_id' => 2, 'branch_id' => 2],
            $monthPillar,
            1,
            CarbonImmutable::parse('2026-03-10 00:00:00'),
            'male',
        );

        $this->assertSame('forward', $forward['direction']);
        $this->assertSame(6, $forward['cycles'][0]['stem_id']);
        $this->assertSame(4, $forward['cycles'][0]['branch_id']);
        $this->assertSame(10.0, $forward['cycles'][0]['end_age_years'] - $forward['cycles'][0]['start_age_years']);
        $this->assertCount(10, $forward['cycles']);

        $this->assertSame('backward', $backward['direction']);
        $this->assertSame(4, $backward['cycles'][0]['stem_id']);
        $this->assertSame(2, $backward['cycles'][0]['branch_id']);
        $this->assertSame(10.0, $backward['cycles'][0]['end_age_years'] - $backward['cycles'][0]['start_age_years']);
        $this->assertCount(10, $backward['cycles']);
    }

    public function test_dayun_does_not_fallback_when_adopted_boundary_event_is_missing(): void
    {
        $this->seedCalendarEvents();
        DB::table('solar_term_events')->where('year', 2027)->delete();

        $this->expectException(CalendarDataUnavailableException::class);

        app(DayunService::class)->calculate(
            ['stem_id' => 1, 'branch_id' => 1],
            ['stem_id' => 5, 'branch_id' => 3],
            1,
            CarbonImmutable::parse('2027-01-01 00:00:00'),
            'male',
        );
    }

    private function seedCalendarEvents(): void
    {
        $this->seed(TaizanMasterSeeder::class);
        $this->seed(SolarTermDefinitionSeeder::class);
        $this->seed(SolarTermEventSeeder::class);
    }
}
