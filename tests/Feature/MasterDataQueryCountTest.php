<?php

declare(strict_types=1);

namespace Tests\Feature;

use Database\Seeders\SolarTermDefinitionSeeder;
use Database\Seeders\SolarTermEventSeeder;
use Database\Seeders\TaizanMasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MasterDataQueryCountTest extends TestCase
{
    use RefreshDatabase;

    private const STATIC_MASTER_TABLES = [
        'master_stems',
        'master_branches',
        'master_elements',
        'master_zokan_ratios',
        'master_ten_gods',
        'master_twelve_life_stages',
        'master_seasonal_multipliers',
    ];

    public function test_single_analysis_queries_each_static_master_at_most_once(): void
    {
        $this->seed(TaizanMasterSeeder::class);
        $this->seed(SolarTermDefinitionSeeder::class);
        $this->seed(SolarTermEventSeeder::class);

        $queries = [];
        DB::listen(function ($query) use (&$queries): void {
            $queries[] = strtolower($query->sql);
        });

        $this->postJson('/api/analyze', [
            'name' => 'クエリ検証者',
            'birthday' => '2026-03-05',
            'birth_time' => '12:00',
            'gender' => 'male',
            'longitude' => 135.0,
            'target_datetime' => '2026-07-01T00:00',
        ])->assertOk()
            ->assertJsonPath('data.schema_version', 1)
            ->assertJsonPath('data.pillars.year.kanji', '丙午')
            ->assertJsonPath('data.pillars.month.kanji', '庚寅')
            ->assertJsonPath('data.pillars.day.kanji', '戊戌')
            ->assertJsonPath('data.pillars.hour.kanji', '戊午');

        $this->assertStaticMastersQueriedAtMostOnce($queries);
        $this->assertMasterSolarTermsWereNotQueried($queries);
    }

    public function test_compatibility_analysis_reuses_static_masters_for_both_people(): void
    {
        $this->seed(TaizanMasterSeeder::class);
        $this->seed(SolarTermDefinitionSeeder::class);
        $this->seed(SolarTermEventSeeder::class);

        $queries = [];
        DB::listen(function ($query) use (&$queries): void {
            $queries[] = strtolower($query->sql);
        });

        $this->postJson('/api/analyze-compatibility', [
            'person1' => [
                'name' => '一人目',
                'birthday' => '2026-03-05',
                'birth_time' => '12:00',
                'gender' => 'male',
                'longitude' => 135.0,
            ],
            'person2' => [
                'name' => '二人目',
                'birthday' => '2026-03-06',
                'birth_time' => '12:00',
                'gender' => 'female',
                'longitude' => 135.0,
            ],
            'target_datetime' => '2026-07-01T00:00',
        ])->assertOk()
            ->assertJsonPath('data.schema_version', 1)
            ->assertJsonPath('data.person1_result.schema_version', 1)
            ->assertJsonPath('data.person2_result.schema_version', 1);

        $this->assertStaticMastersQueriedAtMostOnce($queries);
        $this->assertMasterSolarTermsWereNotQueried($queries);
    }

    /** @param array<int,string> $queries */
    private function assertStaticMastersQueriedAtMostOnce(array $queries): void
    {
        foreach (self::STATIC_MASTER_TABLES as $table) {
            $this->assertLessThanOrEqual(
                1,
                $this->queryCountFor($queries, $table),
                "{$table} should be queried at most once per analysis request.",
            );
        }
    }

    /** @param array<int,string> $queries */
    private function queryCountFor(array $queries, string $table): int
    {
        return count(array_filter(
            $queries,
            static fn (string $sql): bool => str_contains($sql, $table),
        ));
    }

    /** @param array<int,string> $queries */
    private function assertMasterSolarTermsWereNotQueried(array $queries): void
    {
        $this->assertSame(0, $this->queryCountFor($queries, 'master_solar_terms'));
    }
}
