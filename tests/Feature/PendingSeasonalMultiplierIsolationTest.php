<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\AppraisalService;
use App\Services\DestinyCalculationService;
use Database\Seeders\SolarTermDefinitionSeeder;
use Database\Seeders\SolarTermEventSeeder;
use Database\Seeders\TaizanMasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PendingSeasonalMultiplierIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_seasonal_values_do_not_affect_appraisal_or_compatibility(): void
    {
        $this->seed(TaizanMasterSeeder::class);
        $this->seed(SolarTermDefinitionSeeder::class);
        $this->seed(SolarTermEventSeeder::class);

        $before1 = $this->analyze('2026-03-10T09:00', 'male');
        $before2 = $this->analyze('2026-03-11T09:00', 'female');
        $compatibilityBefore = app(AppraisalService::class)->compareDestiny($before1, $before2);

        $rawScores = $before1['five_element_strength']['raw_scores'];
        $strongestRaw = $this->strongestElement($rawScores);
        $weakestRaw = $this->weakestElement($rawScores);
        $seasonId = $before1['five_element_strength']['seasonal_adjustment']['season_id'];
        $elementIds = DB::table('master_elements')->pluck('id', 'name');

        DB::table('master_seasonal_multipliers')
            ->where('season_id', $seasonId)
            ->update(['multiplier' => 1.0]);
        DB::table('master_seasonal_multipliers')
            ->where('season_id', $seasonId)
            ->where('element_id', $elementIds[$strongestRaw])
            ->update(['multiplier' => 0.01]);
        DB::table('master_seasonal_multipliers')
            ->where('season_id', $seasonId)
            ->where('element_id', $elementIds[$weakestRaw])
            ->update(['multiplier' => 9.99]);
        app()->forgetScopedInstances();

        $after1 = $this->analyze('2026-03-10T09:00', 'male');
        $after2 = $this->analyze('2026-03-11T09:00', 'female');
        $compatibilityAfter = app(AppraisalService::class)->compareDestiny($after1, $after2);

        $this->assertSame($before1['five_element_strength']['raw_scores'], $after1['five_element_strength']['raw_scores']);
        $this->assertNotSame(
            $strongestRaw,
            $this->strongestElement($after1['five_element_strength']['seasonal_adjusted_scores']),
        );
        $this->assertSame($strongestRaw, $this->strongestPublicElement($after1['five_elements_scores']));
        $this->assertSame($before1['five_elements_scores'], $after1['five_elements_scores']);
        $this->assertSame($before1['appraisal']['balance'], $after1['appraisal']['balance']);
        $this->assertSame($compatibilityBefore, $compatibilityAfter);
        $this->assertSame('pending', $after1['judgement']['strength']['status']);
        $this->assertSame('pending', $after1['judgement']['pattern']['status']);
        $this->assertSame('pending', $after1['judgement']['useful_god']['status']);
    }

    /** @return array<string,mixed> */
    private function analyze(string $birthDateTime, string $gender): array
    {
        return app(DestinyCalculationService::class)->analyze(
            $birthDateTime,
            135.0,
            $gender,
            '2026-07-01T00:00',
        );
    }

    /** @param array<string,float|int> $scores */
    private function strongestElement(array $scores): string
    {
        arsort($scores);

        return (string) array_key_first($scores);
    }

    /** @param array<string,float|int> $scores */
    private function weakestElement(array $scores): string
    {
        asort($scores);

        return (string) array_key_first($scores);
    }

    /** @param array<int,array{element:string,score:float|int}> $scores */
    private function strongestPublicElement(array $scores): string
    {
        usort($scores, static fn (array $left, array $right): int => $right['score'] <=> $left['score']);

        return $scores[0]['element'];
    }
}
