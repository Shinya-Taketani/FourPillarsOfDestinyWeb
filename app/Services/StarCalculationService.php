<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * 通変星・十二運 算出サービス（完全版）
 */
readonly class StarCalculationService
{
    /**
     * 通変星を算出する（日干と対象干の関係）
     */
    public function getTenGod(int $dayStemId, int $targetStemId): string
    {
        $dayStem = DB::table('master_stems')->where('id', $dayStemId)->first();
        $targetStem = DB::table('master_stems')->where('id', $targetStemId)->first();

        if (!$dayStem || !$targetStem) return '不明';

        // 泰山流：相生・相剋の関係性を数値化 (0:比劫, 1:食傷, 2:財星, 3:官星, 4:印星)
        // 木(1) -> 火(2) -> 土(3) -> 金(4) -> 水(5)
        $rel = ($targetStem->element_id - $dayStem->element_id + 5) % 5;
        $isSamePolarity = ($dayStem->is_yang === $targetStem->is_yang);

        // マッピングテーブル
        $matrix = [
            0 => $isSamePolarity ? '比肩' : '劫財',
            1 => $isSamePolarity ? '食神' : '傷官',
            2 => $isSamePolarity ? '偏財' : '正財',
            3 => $isSamePolarity ? '偏官' : '正官',
            4 => $isSamePolarity ? '偏印' : '印綬',
        ];

        return $matrix[$rel] ?? '不明';
    }

    /**
     * 十二運を算出する（日干と対象支の関係）
     */
    public function getTwelveLifeStage(int $dayStemId, int $targetBranchId): string
    {
        // 十二運のマトリックス（日干ごとの各支の十二運ID）
        // 1:胎, 2:養, 3:長生, 4:沐浴, 5:冠帯, 6:建禄, 7:帝旺, 8:衰, 9:病, 10:死, 11:墓, 12:絶
        $matrix = [
            // 子, 丑, 寅, 卯, 辰, 巳, 午, 未, 申, 酉, 戌, 亥
            1 => [9, 11, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12], // 甲(木陽)
            2 => [9, 8, 7, 6, 5, 4, 3, 2, 1, 12, 11, 10],  // 乙(木陰)
            3 => [12, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11], // 丙(火陽)
            4 => [12, 11, 10, 9, 8, 7, 6, 5, 4, 3, 2, 1],  // 丁(火陰)
            5 => [12, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11], // 戊(土陽) - 丙火に同じ
            6 => [12, 11, 10, 9, 8, 7, 6, 5, 4, 3, 2, 1],  // 己(土陰) - 丁火に同じ
            7 => [10, 9, 12, 1, 2, 3, 4, 5, 6, 7, 8, 9],  // 庚(金陽)
            8 => [4, 3, 2, 1, 12, 11, 10, 9, 8, 7, 6, 5],  // 辛(金陰)
            9 => [7, 8, 9, 10, 11, 12, 1, 2, 3, 4, 5, 6], // 壬(水陽)
            10=> [1, 12, 11, 10, 9, 8, 7, 6, 5, 4, 3, 2],  // 癸(水陰)
        ];

        $stageId = $matrix[$dayStemId][$targetBranchId - 1] ?? 1;
        return DB::table('master_twelve_life_stages')->where('id', $stageId)->value('name') ?? '不明';
    }
}
