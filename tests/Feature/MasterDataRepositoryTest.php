<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Repositories\MasterDataRepository;
use Database\Seeders\TaizanJudgmentSeeder;
use Database\Seeders\TaizanMasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MasterDataRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TaizanMasterSeeder::class);
        $this->seed(TaizanJudgmentSeeder::class);
    }

    public function test_master_collections_are_keyed_for_reuse(): void
    {
        $repository = app(MasterDataRepository::class);

        $this->assertSame('甲', $repository->stemsById()->get(1)->name);
        $this->assertSame(1, (int) $repository->stemsByName()->get('甲')->id);
        $this->assertSame('子', $repository->branchesById()->get(1)->name);
        $this->assertSame(1, (int) $repository->branchesByName()->get('子')->id);
        $this->assertSame('木', $repository->elementsById()->get(1)->name);
        $this->assertSame(1, (int) $repository->elementsByName()->get('木')->id);
        $this->assertSame('比肩', $repository->tenGodsById()->get(1)->name);
        $this->assertSame(1, (int) $repository->tenGodsByName()->get('比肩')->id);
        $this->assertSame('胎', $repository->twelveLifeStagesById()->get(1)->name);
        $this->assertNotEmpty($repository->zokanRatiosByBranchId()->get(1));
        $this->assertCount(5, $repository->seasonalMultipliersBySeasonId()->get(1));
        $this->assertCount(5, $repository->seasonalMultipliersByMonthBranchId(3));
    }

    public function test_master_collections_are_memoized_in_same_scope(): void
    {
        $queries = [];
        DB::listen(function ($query) use (&$queries): void {
            $queries[] = strtolower($query->sql);
        });

        $repository = app(MasterDataRepository::class);
        $repository->stemsById();
        $repository->stemsById();
        $repository->stemsByName();
        $repository->branchesById();
        $repository->branchesById();
        $repository->branchesByName();
        $repository->elementsById();
        $repository->elementsById();
        $repository->elementsByName();

        $this->assertSame(1, $this->queryCountFor($queries, 'master_stems'));
        $this->assertSame(1, $this->queryCountFor($queries, 'master_branches'));
        $this->assertSame(1, $this->queryCountFor($queries, 'master_elements'));
        $this->assertSame($repository, app(MasterDataRepository::class));
    }

    /** @param array<int,string> $queries */
    private function queryCountFor(array $queries, string $table): int
    {
        return count(array_filter(
            $queries,
            static fn (string $sql): bool => str_contains($sql, $table),
        ));
    }
}
