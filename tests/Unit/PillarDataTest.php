<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Data\Appraisal\PillarData;
use PHPUnit\Framework\TestCase;

class PillarDataTest extends TestCase
{
    public function test_pillar_data_has_stable_array_and_json_structure(): void
    {
        $pillar = new PillarData(
            stemId: 1,
            branchId: 1,
            stemName: '甲',
            branchName: '子',
        );

        $this->assertSame('甲子', $pillar->pillar());
        $this->assertSame([
            'stem_id' => 1,
            'branch_id' => 1,
            'stem_name' => '甲',
            'branch_name' => '子',
            'pillar' => '甲子',
        ], $pillar->toArray());
        $this->assertSame($pillar->toArray(), json_decode(json_encode($pillar, JSON_THROW_ON_ERROR), true));
    }
}
