<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\RelationJudgementService;
use Tests\TestCase;

class RelationJudgementServiceTest extends TestCase
{
    public function test_stem_combinations_are_detected_without_judging_transformation(): void
    {
        $service = app(RelationJudgementService::class);

        $jiaJi = $service->judgeStemPair($this->stem('year', '甲'), $this->stem('month', '己'));
        $yiGeng = $service->judgeStemPair($this->stem('year', '乙'), $this->stem('month', '庚'));
        $notCombined = $service->judgeStemPair($this->stem('year', '甲'), $this->stem('month', '庚'));

        $this->assertSame('stem_combination', $jiaJi[0]['relation_type']);
        $this->assertSame('土', $jiaJi[0]['transformed_element']);
        $this->assertSame('not_judged', $jiaJi[0]['transformation_status']);
        $this->assertSame('pending', $jiaJi[0]['interpretation_status']);
        $this->assertSame('PENDING', $jiaJi[0]['source_rank']);
        $this->assertSame('金', $yiGeng[0]['transformed_element']);
        $this->assertSame([], $notCombined);
    }

    public function test_branch_pair_relations_are_detected_independently(): void
    {
        $service = app(RelationJudgementService::class);
        $cases = [
            ['子', '丑', 'branch_combination'],
            ['子', '午', 'branch_clash'],
            ['子', '未', 'branch_harm'],
            ['子', '酉', 'branch_break'],
            ['子', '卯', 'branch_punishment'],
        ];

        foreach ($cases as [$first, $second, $relationType]) {
            $relations = $service->judgeBranchPair(
                $this->branch('year', $first),
                $this->branch('month', $second),
            );

            $this->assertContains($relationType, array_column($relations, 'relation_type'));
        }
    }

    public function test_overlapping_pair_relations_are_returned_without_priority(): void
    {
        $relations = app(RelationJudgementService::class)->judgeBranchPair(
            $this->branch('year', '寅'),
            $this->branch('month', '亥'),
        );

        $types = array_column($relations, 'relation_type');
        $this->assertContains('branch_combination', $types);
        $this->assertContains('branch_break', $types);
    }

    public function test_three_member_branch_relations_are_detected(): void
    {
        $service = app(RelationJudgementService::class);

        $punishment = $service->judgeBranchTriple(
            $this->branch('year', '寅'),
            $this->branch('month', '巳'),
            $this->branch('day', '申'),
        );
        $harmony = $service->judgeBranchTriple(
            $this->branch('year', '申'),
            $this->branch('month', '子'),
            $this->branch('day', '辰'),
        );
        $directional = $service->judgeBranchTriple(
            $this->branch('year', '寅'),
            $this->branch('month', '卯'),
            $this->branch('day', '辰'),
        );

        $this->assertSame('branch_punishment', $punishment[0]['relation_type']);
        $this->assertSame('three_punishment', $punishment[0]['relation_variant']);
        $this->assertSame('three_harmony', $harmony[0]['relation_type']);
        $this->assertSame('水', $harmony[0]['element']);
        $this->assertSame('directional_combination', $directional[0]['relation_type']);
        $this->assertSame('木', $directional[0]['element']);
    }

    public function test_self_punishment_requires_two_matching_branches(): void
    {
        $service = app(RelationJudgementService::class);

        $established = $service->judgeBranchPair(
            $this->branch('year', '辰'),
            $this->branch('month', '辰'),
        );
        $notEstablished = $service->judgeBranchPair(
            $this->branch('year', '辰'),
            $this->branch('month', '巳'),
        );

        $this->assertSame('self_punishment', $established[0]['relation_variant']);
        $this->assertSame([], $notEstablished);
    }

    public function test_natal_relations_have_structured_pending_results_without_scores(): void
    {
        $relations = app(RelationJudgementService::class)->judgeNatalRelations($this->chart());
        $types = array_column($relations, 'relation_type');

        $this->assertContains('stem_combination', $types);
        $this->assertContains('branch_clash', $types);
        $this->assertContains('three_harmony', $types);

        foreach ($relations as $relation) {
            $this->assertSame('natal', $relation['target_scope']);
            $this->assertTrue($relation['is_established']);
            $this->assertSame('PENDING', $relation['source_rank']);
            $this->assertStringContainsString('要確認', $relation['source_note']);
            $this->assertArrayHasKey('subjects', $relation);
            $this->assertArrayNotHasKey('score', $relation);
        }
    }

    public function test_future_dayun_entry_uses_natal_vs_dayun_scope(): void
    {
        $relations = app(RelationJudgementService::class)->judgeNatalWithDayun(
            $this->chart(),
            ['kanji' => '己丑'],
        );

        $stemCombination = collect($relations)->firstWhere('relation_type', 'stem_combination');

        $this->assertNotNull($stemCombination);
        $this->assertSame('natal_vs_dayun', $stemCombination['target_scope']);
        $this->assertSame('dayun', $stemCombination['subjects'][1]['pillar']);
    }

    private function chart(): array
    {
        return [
            'pillars' => [
                'year' => ['kanji' => '甲申'],
                'month' => ['kanji' => '己子'],
                'day' => ['kanji' => '丙辰'],
                'hour' => ['kanji' => '辛午'],
            ],
        ];
    }

    private function stem(string $pillar, string $name): array
    {
        return ['pillar' => $pillar, 'type' => 'stem', 'name' => $name];
    }

    private function branch(string $pillar, string $name): array
    {
        return ['pillar' => $pillar, 'type' => 'branch', 'name' => $name];
    }
}
