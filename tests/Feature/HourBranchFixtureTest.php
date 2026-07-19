<?php

namespace Tests\Feature;

use App\Services\SexagenaryService;
use Carbon\CarbonImmutable;
use Database\Seeders\TaizanMasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\CsvFixtureLoader;
use Tests\TestCase;

class HourBranchFixtureTest extends TestCase
{
    use RefreshDatabase;

    public function test_hour_branch_boundaries_from_csv_fixture(): void
    {
        $this->seed(TaizanMasterSeeder::class);

        $service = app(SexagenaryService::class);

        foreach (CsvFixtureLoader::load('hour_branch_samples.csv') as $sample) {
            $dateTime = CarbonImmutable::parse($sample['birth_datetime'], $sample['timezone']);
            $expectedBranchId = (int) DB::table('master_branches')
                ->where('name', $sample['expected_branch_name'])
                ->value('id');

            $this->assertSame(
                $expectedBranchId,
                $service->resolveHourBranchId($dateTime),
                $sample['sample_id'],
            );
        }
    }
}
