<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\GetsuunService;
use App\Services\SexagenaryService;
use Database\Seeders\TaizanMasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetsuunCalculationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TaizanMasterSeeder::class);
    }

    public function test_month_stems_are_resolved_from_each_target_year_stem(): void
    {
        $service = app(GetsuunService::class);

        $months2025 = $service->calculate(2025, 1);
        $months2026 = $service->calculate(2026, 1);
        $months2027 = $service->calculate(2027, 1);

        $this->assertSame(5, $months2025[0]['stem_id']);
        $this->assertSame(7, $months2026[0]['stem_id']);
        $this->assertSame(9, $months2027[0]['stem_id']);
        $this->assertSame('庚寅', $months2026[0]['kanji']);
        $this->assertNotSame($months2025[0]['stem_id'], $months2026[0]['stem_id']);
        $this->assertNotSame($months2026[0]['stem_id'], $months2027[0]['stem_id']);
    }

    public function test_month_branch_order_remains_tiger_through_ox(): void
    {
        $months = app(GetsuunService::class)->calculate(2026, 1);

        $this->assertSame([3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 1, 2], array_column($months, 'branch_id'));
        $this->assertSame(['2月', '3月', '4月', '5月', '6月', '7月', '8月', '9月', '10月', '11月', '12月', '1月'], array_column($months, 'month_name'));
    }

    public function test_month_pillar_helper_uses_existing_five_tigers_rule(): void
    {
        $service = app(SexagenaryService::class);

        $this->assertSame(['stem_id' => 5, 'branch_id' => 3], $service->getMonthPillarIds(2, 3));
        $this->assertSame(['stem_id' => 7, 'branch_id' => 3], $service->getMonthPillarIds(3, 3));
        $this->assertSame(['stem_id' => 9, 'branch_id' => 3], $service->getMonthPillarIds(4, 3));
    }
}
