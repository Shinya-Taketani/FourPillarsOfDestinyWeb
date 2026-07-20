<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Data\Appraisal\AppraisalResultData;
use PHPUnit\Framework\TestCase;

class AppraisalResultDataTest extends TestCase
{
    public function test_result_data_is_json_serializable_with_stable_and_legacy_keys(): void
    {
        $result = AppraisalResultData::fromCalculation(
            legacyResult: $this->legacyResult(),
            input: [
                'birth_date' => '2026-03-05',
                'birth_time' => '22:30',
                'gender' => 'male',
                'longitude' => 135.0,
                'input_timezone' => 'Asia/Tokyo',
                'target_year' => 2026,
            ],
            calculationMetadata: [
                'calendar_policy' => ['day_boundary' => '23:00'],
                'warnings' => [],
            ],
            warnings: [],
        );

        $array = $result->toArray();
        $json = json_encode($result, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);

        foreach ([
            'schema_version',
            'status',
            'calculation_metadata',
            'chart',
            'hidden_stems',
            'ten_gods',
            'twelve_life_stages',
            'five_element_strength',
            'relations',
            'dayun',
            'ryunen',
            'judgement',
            'interpretation',
            'warnings',
        ] as $key) {
            $this->assertArrayHasKey($key, $array);
        }

        foreach (['year', 'month', 'day', 'hour'] as $pillarName) {
            $this->assertArrayHasKey($pillarName, $array['chart']);
        }

        $this->assertSame(1, $array['schema_version']);
        $this->assertSame('calculated', $array['status']);
        $this->assertSame('pending', $array['relations']['status']);
        $this->assertSame('PENDING', $array['relations']['source_rank']);
        $this->assertNull($array['ryunen']['active_dayun']);
        $this->assertArrayHasKey('pillars', $array);
        $this->assertArrayHasKey('five_elements_scores', $array);
        $this->assertArrayHasKey('relation_items', $array);
        $this->assertSame($array, json_decode($json, true, flags: JSON_THROW_ON_ERROR));
    }

    /** @return array<string,mixed> */
    private function legacyResult(): array
    {
        $pillar = [
            'stem_id' => 1,
            'branch_id' => 1,
            'stem_name' => '甲',
            'branch_name' => '子',
            'pillar' => '甲子',
            'kanji' => '甲子',
            'ten_god' => ['name' => '比肩'],
            'zokan' => ['stem_id' => 10, 'name' => '癸', 'ten_god_name' => '印綬'],
            'twelve_life_stage' => ['name' => '沐浴'],
        ];

        return [
            'pillars' => [
                'year' => $pillar,
                'month' => $pillar,
                'day' => $pillar,
                'hour' => $pillar,
            ],
            'five_elements_scores' => [],
            'five_element_strength' => ['status' => 'pending'],
            'relations' => [],
            'dayun' => ['cycles' => []],
            'ryunen' => ['active_dayun' => null],
            'judgement' => [
                'strength' => ['status' => 'pending'],
                'relations' => [
                    'status' => 'pending',
                    'items' => [],
                    'source_rank' => 'PENDING',
                    'source_note' => '要確認',
                ],
            ],
            'appraisal' => ['personality' => '検証用'],
        ];
    }
}
