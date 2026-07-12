<?php

namespace Tests\Feature;

use App\Exceptions\CalendarDataUnavailableException;
use App\Services\RyunenService;
use App\Services\SolarTermService;
use Carbon\CarbonImmutable;
use Database\Seeders\SolarTermDefinitionSeeder;
use Database\Seeders\SolarTermEventSeeder;
use Database\Seeders\TaizanMasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CsvFixtureLoader;
use Tests\TestCase;

class MissingCalendarDataFixtureTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_calendar_data_from_csv_fixture_is_not_approximated(): void
    {
        $this->seedCalendarEvents();

        $solarTermService = app(SolarTermService::class);
        $ryunenService = app(RyunenService::class);

        foreach (CsvFixtureLoader::load('missing_calendar_data_samples.csv') as $sample) {
            $dateTime = CarbonImmutable::parse($sample['target_datetime'], $sample['timezone']);

            $this->assertNull(
                $solarTermService->getLichunDateTime($dateTime->year),
                $sample['sample_id'],
            );

            try {
                $ryunenService->resolveRyunenYear($dateTime);
                $this->fail($sample['sample_id'] . ' should throw CalendarDataUnavailableException.');
            } catch (CalendarDataUnavailableException) {
                $this->assertSame('calendar_data_unavailable', $sample['expected_behavior']);
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
