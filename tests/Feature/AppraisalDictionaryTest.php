<?php

namespace Tests\Feature;

use App\Services\AppraisalService;
use Tests\TestCase;

class AppraisalDictionaryTest extends TestCase
{
    public function test_appraisal_response_keys_are_preserved_and_text_comes_from_dictionary(): void
    {
        config([
            'taizan_interpretations.saiun_comments.比肩' => '辞書経由の歳運コメント',
            'taizan_interpretations.dayun_comments.正官' => '辞書経由の大運コメント',
        ]);

        $result = app(AppraisalService::class)->generate($this->destinyData(), [
            ['element' => '木', 'score' => 5],
            ['element' => '火', 'score' => 3],
        ]);

        $this->assertArrayHasKey('personality', $result);
        $this->assertArrayHasKey('work', $result);
        $this->assertArrayHasKey('balance', $result);
        $this->assertArrayHasKey('dayun_comments', $result);
        $this->assertArrayHasKey('saiun_comment', $result);
        $this->assertArrayHasKey('getsuun_comments', $result);
        $this->assertArrayHasKey('nichiun_meanings', $result);
        $this->assertArrayHasKey('relations', $result);
        $this->assertArrayHasKey('judgement', $result);
        $this->assertSame('辞書経由の歳運コメント', $result['saiun_comment']);
        $this->assertSame('辞書経由の大運コメント', $result['dayun_comments'][0]['comment']);
        $this->assertSame('pending', $result['judgement']['strength']['status']);
    }

    public function test_compatibility_response_shape_is_preserved(): void
    {
        $result = app(AppraisalService::class)->compareDestiny(
            $this->compatibilityPerson('甲子', '甲子', '木'),
            $this->compatibilityPerson('己丑', '己丑', '木'),
        );

        $this->assertArrayHasKey('total_score', $result);
        $this->assertArrayHasKey('details', $result);
        $this->assertArrayHasKey('summary', $result);
        $this->assertArrayHasKey('year_relation', $result['details']);
        $this->assertArrayHasKey('stem_relation', $result['details']);
        $this->assertArrayHasKey('balance_relation', $result['details']);
        $this->assertArrayHasKey('conclusion', $result['details']['balance_relation']);
        $this->assertArrayHasKey('advice', $result['details']['balance_relation']);
    }

    private function destinyData(): array
    {
        return [
            'pillars' => [
                'day' => ['kanji' => '甲子'],
                'month' => ['ten_god' => ['name' => '正官']],
            ],
            'dayun' => [
                'cycles' => [
                    ['ten_god' => '正官'],
                ],
            ],
            'saiun' => ['ten_god' => '比肩'],
            'getsuun' => [
                ['ten_god' => '食神'],
            ],
            'judgement' => [
                'strength' => ['status' => 'pending'],
            ],
            'relations' => [
                ['relation_type' => 'branch_clash'],
            ],
        ];
    }

    private function compatibilityPerson(string $yearPillar, string $dayPillar, string $strongElement): array
    {
        return [
            'pillars' => [
                'year' => ['kanji' => $yearPillar],
                'day' => ['kanji' => $dayPillar],
            ],
            'five_elements_scores' => [
                ['element' => $strongElement, 'score' => 5],
                ['element' => '火', 'score' => 1],
            ],
        ];
    }
}
