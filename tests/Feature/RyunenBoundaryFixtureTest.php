<?php

namespace Tests\Feature;

use App\Services\RyunenService;
use Carbon\CarbonImmutable;
use Database\Seeders\SolarTermDefinitionSeeder;
use Database\Seeders\SolarTermEventSeeder;
use Database\Seeders\TaizanMasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CsvFixtureLoader;
use Tests\TestCase;

class RyunenBoundaryFixtureTest extends TestCase
{
    use RefreshDatabase;

    public function test_ryunen_boundaries_from_csv_fixture(): void
    {
        $this->seedCalendarEvents();

        $service = app(RyunenService::class);

        foreach (CsvFixtureLoader::load('ryunen_boundary_samples.csv') as $sample) {
            $dateTime = CarbonImmutable::parse($sample['target_datetime'], $sample['timezone']);

            $this->assertSame(
                (int) $sample['expected_ryunen_year'],
                $service->resolveRyunenYear($dateTime),
                $sample['sample_id'],
            );
        }
    }

    private function seedCalendarEvents(): void
    {
        $this->seed(TaizanMasterSeeder::class);
        $this->seed(SolarTermDefinitionSeeder::class);
        $this->seed(SolarTermEventSeeder::class);
    }
}
