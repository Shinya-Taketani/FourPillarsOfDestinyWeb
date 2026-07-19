<?php

namespace Tests\Feature;

use App\Services\JudgementService;
use Tests\TestCase;

class JudgementServiceTest extends TestCase
{
    public function test_judgement_service_returns_pending_structured_result(): void
    {
        $judgement = app(JudgementService::class)->evaluate($this->destinyData());

        foreach (['strength', 'pattern', 'useful_god', 'favorable_unfavorable', 'relations'] as $key) {
            $this->assertArrayHasKey($key, $judgement);
            $this->assertSame('pending', $judgement[$key]['status']);
            $this->assertSame('PENDING', $judgement[$key]['source_rank']);
            $this->assertStringContainsString('要確認', $judgement[$key]['source_note']);
            if ($key !== 'relations') {
                $this->assertArrayHasKey('basis', $judgement[$key]);
            }
        }

        $this->assertNull($judgement['strength']['label']);
        $this->assertNull($judgement['pattern']['label']);
        $this->assertNull($judgement['useful_god']['label']);
        $this->assertSame([], $judgement['useful_god']['candidates']);
        $this->assertSame([], $judgement['favorable_unfavorable']['favorable']);
        $this->assertSame([], $judgement['favorable_unfavorable']['unfavorable']);
        $this->assertSame($this->destinyData()['relations'], $judgement['relations']['items']);
    }

    public function test_judgement_basis_collects_materials_without_deciding_rules(): void
    {
        $judgement = app(JudgementService::class)->evaluate($this->destinyData());

        $this->assertSame('甲', $judgement['strength']['basis']['day_stem']);
        $this->assertSame('卯', $judgement['strength']['basis']['month_branch']);
        $this->assertSame('甲卯', $judgement['pattern']['basis']['month_pillar']);
        $this->assertSame('正官', $judgement['pattern']['basis']['month_ten_god']);
        $this->assertSame('木', $judgement['strength']['basis']['strongest_elements'][0]['element']);
        $this->assertSame('水', $judgement['strength']['basis']['weakest_elements'][0]['element']);
        $this->assertSame(
            'PENDING',
            $judgement['strength']['basis']['five_element_strength']['source_rank'],
        );
        $this->assertSame('pending', $judgement['strength']['status']);
        $this->assertNull($judgement['useful_god']['label']);
    }

    private function destinyData(): array
    {
        return [
            'pillars' => [
                'year' => [
                    'kanji' => '丙午',
                    'ten_god' => ['name' => '食神'],
                    'zokan' => ['name' => '丁', 'ten_god_name' => '傷官'],
                ],
                'month' => [
                    'kanji' => '甲卯',
                    'ten_god' => ['name' => '正官'],
                    'zokan' => ['name' => '乙', 'ten_god_name' => '劫財'],
                ],
                'day' => [
                    'kanji' => '甲子',
                    'ten_god' => ['name' => '比肩'],
                    'zokan' => ['name' => '癸', 'ten_god_name' => '印綬'],
                ],
                'hour' => [
                    'kanji' => '乙丑',
                    'ten_god' => ['name' => '劫財'],
                    'zokan' => ['name' => '己', 'ten_god_name' => '正財'],
                ],
            ],
            'five_elements_scores' => [
                ['element' => '木', 'score' => 5.0],
                ['element' => '火', 'score' => 3.0],
                ['element' => '土', 'score' => 2.0],
                ['element' => '金', 'score' => 1.0],
                ['element' => '水', 'score' => 0.5],
            ],
            'five_element_strength' => [
                'raw_scores' => ['木' => 5.0, '火' => 3.0, '土' => 2.0, '金' => 1.0, '水' => 0.5],
                'seasonal_adjusted_scores' => ['木' => 5.0, '火' => 3.0, '土' => 2.0, '金' => 1.0, '水' => 0.5],
                'source_rank' => 'PENDING',
                'source_note' => '配点・季節補正は要確認。',
            ],
            'relations' => [
                [
                    'relation_type' => 'branch_clash',
                    'target_scope' => 'natal',
                    'subjects' => [],
                    'is_established' => true,
                    'interpretation_status' => 'pending',
                    'source_rank' => 'PENDING',
                    'source_note' => '優先順位・吉凶判断は要確認。',
                ],
            ],
        ];
    }
}
