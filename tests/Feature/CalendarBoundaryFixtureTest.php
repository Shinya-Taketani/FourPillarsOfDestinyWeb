<?php

namespace Tests\Feature;

use App\Services\SexagenaryService;
use App\Services\SolarTermService;
use Carbon\CarbonImmutable;
use Database\Seeders\SolarTermDefinitionSeeder;
use Database\Seeders\SolarTermEventSeeder;
use Database\Seeders\TaizanMasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CsvFixtureLoader;
use Tests\TestCase;

class CalendarBoundaryFixtureTest extends TestCase
{
    use RefreshDatabase;

    public function test_calendar_boundaries_from_csv_fixture(): void
    {
        $this->seedCalendarEvents();

        $sexagenaryService = app(SexagenaryService::class);
        $solarTermService = app(SolarTermService::class);

        foreach (CsvFixtureLoader::load('calendar_boundary_samples.csv') as $sample) {
            $dateTime = CarbonImmutable::parse($sample['target_datetime'], $sample['timezone']);

            if ($sample['boundary_type'] === 'year') {
                $pillarYear = $sexagenaryService->getPillarYear($dateTime);
                $this->assertSame(
                    $sample['expected_relation'] === 'before' ? 2025 : 2026,
                    $pillarYear,
                    $sample['sample_id'],
                );
                continue;
            }

            $event = $solarTermService->getLatestMonthBoundaryEvent($dateTime);

            if ($sample['expected_relation'] === 'before') {
                $this->assertNotSame($sample['term_name'], $event?->term_name, $sample['sample_id']);
            } else {
                $this->assertSame($sample['term_name'], $event?->term_name, $sample['sample_id']);
            }
        }
    }

    private function seedCalendarEvents(): void
    {
        $this->seed(TaizanMasterSeeder::class);
        $this->seed(SolarTermDefinitionSeeder::class);
        $this->seed(SolarTermEventSeeder::class);
    }
}
