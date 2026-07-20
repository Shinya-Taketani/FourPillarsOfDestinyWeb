<?php

namespace Tests\Feature;

use App\Services\InterpretationDictionaryService;
use Database\Seeders\TaizanJudgmentSeeder;
use Database\Seeders\TaizanMasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InterpretationDictionaryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_known_ten_god_text_is_loaded_from_dictionary(): void
    {
        $entry = app(InterpretationDictionaryService::class)->getTenGodText('比肩');

        $this->assertSame('意志を貫く。', $entry['summary']);
        $this->assertSame('PENDING', $entry['source_rank']);
        $this->assertStringContainsString('要確認', $entry['source_note']);
    }

    public function test_known_twelve_life_stage_text_is_loaded_from_master_table(): void
    {
        $this->seed(TaizanMasterSeeder::class);
        $this->seed(TaizanJudgmentSeeder::class);

        $entry = app(InterpretationDictionaryService::class)->getTwelveLifeStageText('帝旺');

        $this->assertStringContainsString('エネルギーは最大', $entry['summary']);
        $this->assertSame('PENDING', $entry['source_rank']);
        $this->assertStringContainsString('要確認', $entry['source_note']);
    }

    public function test_unknown_key_returns_explicit_pending_fallback(): void
    {
        $entry = app(InterpretationDictionaryService::class)->getFallbackText('ten_gods', '未定義');

        $this->assertSame('この項目の解釈文は未登録です。', $entry['text']);
        $this->assertSame('PENDING', $entry['source_rank']);
        $this->assertSame('ten_gods', $entry['category']);
        $this->assertSame('未定義', $entry['key']);
    }
}
