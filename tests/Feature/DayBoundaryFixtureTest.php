<?php

namespace Tests\Feature;

use App\Services\SexagenaryService;
use Carbon\CarbonImmutable;
use Tests\Support\CsvFixtureLoader;
use Tests\TestCase;

class DayBoundaryFixtureTest extends TestCase
{
    public function test_day_boundary_from_csv_fixture(): void
    {
        $service = app(SexagenaryService::class);

        foreach (CsvFixtureLoader::load('day_boundary_samples.csv') as $sample) {
            $dateTime = CarbonImmutable::parse($sample['birth_datetime'], $sample['timezone']);

            $this->assertSame(
                $sample['expected_calculation_date'],
                $service->getDayPillarCalculationDate($dateTime)->toDateString(),
                $sample['sample_id'],
            );
        }
    }
}
