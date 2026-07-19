<?php

namespace Tests\Feature;

use App\Services\ZokanService;
use Carbon\CarbonImmutable;
use Database\Seeders\TaizanMasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ZokanMasterTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_zokan_ratios_has_stem_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn('master_zokan_ratios', 'stem_id'));
    }

    public function test_taizan_master_seeder_sets_zokan_stem_ids(): void
    {
        $this->seed(TaizanMasterSeeder::class);

        $cases = [
            ['branch' => '子', 'type' => 'honki', 'stem' => '癸'],
            ['branch' => '寅', 'type' => 'honki', 'stem' => '甲'],
            ['branch' => '午', 'type' => 'honki', 'stem' => '丁'],
            ['branch' => '酉', 'type' => 'honki', 'stem' => '辛'],
        ];

        foreach ($cases as $case) {
            $actualStem = DB::table('master_zokan_ratios')
                ->join('master_branches', 'master_branches.id', '=', 'master_zokan_ratios.branch_id')
                ->join('master_stems', 'master_stems.id', '=', 'master_zokan_ratios.stem_id')
                ->where('master_branches.name', $case['branch'])
                ->where('master_zokan_ratios.type', $case['type'])
                ->value('master_stems.name');

            $this->assertSame($case['stem'], $actualStem);
        }
    }

    public function test_zokan_service_returns_stem_id_from_master_zokan_ratios(): void
    {
        $this->seed(TaizanMasterSeeder::class);

        $service = app(ZokanService::class);
        $startedAt = '2026-01-01 00:00:00';

        $cases = [
            ['branch' => '子', 'days' => 15, 'stem' => '癸'],
            ['branch' => '寅', 'days' => 15, 'stem' => '甲'],
            ['branch' => '午', 'days' => 25, 'stem' => '丁'],
            ['branch' => '酉', 'days' => 15, 'stem' => '辛'],
        ];

        foreach ($cases as $case) {
            $branchId = DB::table('master_branches')->where('name', $case['branch'])->value('id');
            $expectedStemId = DB::table('master_stems')->where('name', $case['stem'])->value('id');

            $actualStemId = $service->getZokanStemId(
                (int)$branchId,
                CarbonImmutable::parse($startedAt)->addDays($case['days']),
                $startedAt,
            );

            $this->assertSame((int)$expectedStemId, $actualStemId);
        }
    }

    public function test_zokan_service_reuses_ratios_for_repeated_lookups(): void
    {
        $this->seed(TaizanMasterSeeder::class);
        $queries = [];

        DB::listen(function ($query) use (&$queries): void {
            $queries[] = strtolower($query->sql);
        });

        $service = app(ZokanService::class);
        $startedAt = '2026-01-01 00:00:00';
        $dateTime = CarbonImmutable::parse($startedAt)->addDays(15);

        $service->getZokanStemId(1, $dateTime, $startedAt);
        $service->getZokanStemId(1, $dateTime, $startedAt);

        $this->assertSame(1, count(array_filter(
            $queries,
            static fn (string $sql): bool => str_contains($sql, 'master_zokan_ratios'),
        )));
    }
}
