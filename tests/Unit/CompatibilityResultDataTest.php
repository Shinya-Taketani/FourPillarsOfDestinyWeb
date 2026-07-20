<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Data\Appraisal\CompatibilityResultData;
use PHPUnit\Framework\TestCase;

class CompatibilityResultDataTest extends TestCase
{
    public function test_compatibility_result_keeps_legacy_aliases_and_is_json_serializable(): void
    {
        $person1 = [
            'schema_version' => 1,
            'warnings' => [[
                'code' => 'TAIZAN_RULE_PENDING',
                'message' => '要確認',
                'severity' => 'warning',
            ]],
        ];
        $person2 = $person1;
        $compatibility = [
            'total_score' => 50,
            'summary' => '検証用',
            'details' => [],
        ];
        $result = new CompatibilityResultData(1, $person1, $person2, $compatibility);
        $array = $result->toArray();

        $this->assertSame($person1, $array['person1_result']);
        $this->assertSame($person1, $array['person1']);
        $this->assertSame($person2, $array['person2_result']);
        $this->assertSame($person2, $array['person2']);
        $this->assertSame('pending', $array['relations']['status']);
        $this->assertSame('PENDING', $array['interpretation']['source_rank']);
        $this->assertCount(1, $array['warnings']);
        $this->assertSame($array, json_decode(json_encode($result, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR));
    }
}
