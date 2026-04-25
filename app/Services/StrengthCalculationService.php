<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * 泰山流・力量計算サービス
 * 五行の配分と季節倍率（旺相死囚休）を統合してスコア化する。
 */
readonly class StrengthCalculationService
{
    /**
     * 力量スコアの算出
     */
    public function calculate(array $pillars, int $seasonId): array
    {
        // 1. 五行ごとの基礎点を初期化
        $scores = [
            1 => 0.0, // 木
            2 => 0.0, // 火
            3 => 0.0, // 土
            4 => 0.0, // 金
            5 => 0.0, // 水
        ];

        // 2. 天干の力量加算 (基礎点 1.0)
        foreach ($pillars as $pillar) {
            $stem = DB::table('master_stems')->where('id', $pillar['stem_id'])->first();
            if ($stem) {
                $scores[$stem->element_id] += 1.0;
            }
        }

        // 3. 地支（蔵干）の力量加算
        foreach ($pillars as $pillar) {
            $ratios = DB::table('master_zokan_ratios')
                        ->where('branch_id', $pillar['branch_id'])
                        ->get();
            
            foreach ($ratios as $ratio) {
                // 蔵干の日数比率に応じて加算 (例: 30日中の18日なら 0.6点)
                $scores[$ratio->element_id] += ($ratio->days / 30.0);
            }
        }

        // 4. 季節倍率（旺相死囚休）の適用
        $multipliers = DB::table('master_seasonal_multipliers')
                         ->where('season_id', $seasonId)
                         ->get()
                         ->keyBy('element_id');

        foreach ($scores as $elementId => $baseScore) {
            $multiplier = $multipliers[$elementId]->multiplier ?? 1.0;
            $scores[$elementId] = round($baseScore * (float)$multiplier, 2);
        }

        return $this->formatResult($scores);
    }

    private function formatResult(array $scores): array
    {
        $elements = DB::table('master_elements')->get()->keyBy('id');
        $result = [];

        foreach ($scores as $id => $score) {
            $result[] = [
                'element' => $elements[$id]->name,
                'score'   => $score,
                'color'   => $elements[$id]->color_code,
            ];
        }

        return $result;
    }
}
