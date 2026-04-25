<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * 通変星・十二運 算出サービス
 */
readonly class StarCalculationService
{
    /**
     * 通変星の特定（日干と対象の干の組み合わせ）
     */
    public function getTenGod(int $dayStemId, int $targetStemId): array
    {
        // 本来は「生剋」と「陰陽」の組み合わせで判定しますが、
        // 今回はマスタデータを参照するシンプルなロジックを構築します
        // ※泰山流のロジックに基づいてIDを算出する部分は、後の精密化フェーズで実装
        
        $star = DB::table('master_ten_gods')->where('id', 1)->first(); // 仮で比肩

        return [
            'id' => $star->id,
            'name' => $star->name,
        ];
    }

    /**
     * 十二運の特定（日干と対象の支の組み合わせ）
     */
    public function getTwelveLifeStage(int $dayStemId, int $targetBranchId): array
    {
        // 十二運マスタから取得
        // ※ここも本来は「十二運表」に基づくマッピングが必要
        $stage = DB::table('master_twelve_life_stages')->where('id', 6)->first(); // 仮で建禄

        return [
            'id' => $stage->id,
            'name' => $stage->name,
            'score' => $stage->score,
        ];
    }
}
