<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\FiveElementStrengthService;
use Database\Seeders\TaizanMasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class FiveElementStrengthServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TaizanMasterSeeder::class);
    }

    public function test_it_returns_verifiable_five_element_strength_structure(): void
    {
        $result = app(FiveElementStrengthService::class)->calculate($this->pillars(), 4);
        $elementNames = ['木', '火', '土', '金', '水'];

        $this->assertSame($elementNames, array_keys($result['raw_scores']));
        $this->assertSame($elementNames, array_keys($result['seasonal_adjusted_scores']));
        $this->assertSame($elementNames, array_keys($result['normalized_scores']));
        $this->assertSame($elementNames, array_keys($result['seasonal_status']));
        $this->assertArrayHasKey('heavenly_stems', $result['basis']);
        $this->assertArrayHasKey('earthly_branches', $result['basis']);
        $this->assertArrayHasKey('hidden_stems', $result['basis']);
        $this->assertArrayHasKey('seasonal_multiplier', $result['basis']);
        $this->assertSame('PENDING', $result['source_rank']);
        $this->assertStringContainsString('要確認', $result['source_note']);
        $this->assertTrue($result['seasonal_adjustment']['applied']);
        $this->assertSame(1, $result['seasonal_adjustment']['season_id']);
        $this->assertSame('旺', $result['seasonal_status']['木']);
        $this->assertNotEmpty($result['basis']['hidden_stems']);

        foreach ($result['basis']['hidden_stems'] as $hiddenStem) {
            $this->assertIsInt($hiddenStem['stem_id']);
        }
    }

    public function test_hidden_stem_scores_use_stem_id_instead_of_element_id(): void
    {
        DB::table('master_zokan_ratios')
            ->where('branch_id', 1)
            ->where('type', 'honki')
            ->update(['element_id' => 1]);

        $pillars = array_fill_keys(['year', 'month', 'day', 'hour'], [
            'stem_id' => 1,
            'branch_id' => 1,
        ]);

        $result = app(FiveElementStrengthService::class)->calculate($pillars, 1);

        $this->assertSame(4.0, $result['component_scores']['hidden_stems']['水']);
        $this->assertSame(0.0, $result['component_scores']['hidden_stems']['木']);
    }

    public function test_null_hidden_stem_id_is_not_silently_inferred_from_element_id(): void
    {
        DB::table('master_zokan_ratios')
            ->where('branch_id', 1)
            ->where('type', 'honki')
            ->update(['stem_id' => null]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Zokan stem_id is not set');

        app(FiveElementStrengthService::class)->calculate($this->pillars(), 4);
    }

    public function test_month_branch_is_resolved_to_its_season_before_applying_multipliers(): void
    {
        $result = app(FiveElementStrengthService::class)->calculate($this->pillars(), 7);

        $this->assertSame('午', $result['seasonal_adjustment']['month_branch']);
        $this->assertSame(2, $result['seasonal_adjustment']['season_id']);
        $this->assertSame('旺', $result['seasonal_status']['火']);
        $this->assertSame(2.0, $result['basis']['seasonal_multiplier']['火']['multiplier']);
    }

    public function test_incomplete_seasonal_multipliers_are_reported_as_pending(): void
    {
        DB::table('master_seasonal_multipliers')
            ->where('season_id', 1)
            ->where('element_id', 1)
            ->delete();

        $result = app(FiveElementStrengthService::class)->calculate($this->pillars(), 4);

        $this->assertFalse($result['seasonal_adjustment']['applied']);
        $this->assertSame('pending', $result['seasonal_adjustment']['status']);
        $this->assertSame('PENDING', $result['seasonal_status']['木']);
        $this->assertSame($result['raw_scores'], $result['seasonal_adjusted_scores']);
        $this->assertStringContainsString('補正未適用', $result['seasonal_adjustment']['source_note']);
    }

    public function test_compatibility_scores_keep_the_existing_api_shape(): void
    {
        $service = app(FiveElementStrengthService::class);
        $scores = $service->compatibilityScores($service->calculate($this->pillars(), 4));

        $this->assertCount(5, $scores);
        $this->assertSame(['element', 'score', 'color'], array_keys($scores[0]));
    }

    private function pillars(): array
    {
        return [
            'year' => ['stem_id' => 1, 'branch_id' => 3],
            'month' => ['stem_id' => 2, 'branch_id' => 4],
            'day' => ['stem_id' => 3, 'branch_id' => 1],
            'hour' => ['stem_id' => 4, 'branch_id' => 2],
        ];
    }
}
