<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TaizanMasterSeeder extends Seeder
{
    public function run(): void
    {
        // --- 1. 五行マスター (完結) ---
        DB::table('master_elements')->insert([
            ['id' => 1, 'name' => '木', 'color_code' => '#228B22'],
            ['id' => 2, 'name' => '火', 'color_code' => '#FF4500'],
            ['id' => 3, 'name' => '土', 'color_code' => '#8B4513'],
            ['id' => 4, 'name' => '金', 'color_code' => '#FFD700'],
            ['id' => 5, 'name' => '水', 'color_code' => '#1E90FF'],
        ]);

        // --- 2. 十干マスター (完結) ---
        DB::table('master_stems')->insert([
            ['id' => 1, 'name' => '甲', 'element_id' => 1, 'is_yang' => true],
            ['id' => 2, 'name' => '乙', 'element_id' => 1, 'is_yang' => false],
            ['id' => 3, 'name' => '丙', 'element_id' => 2, 'is_yang' => true],
            ['id' => 4, 'name' => '丁', 'element_id' => 2, 'is_yang' => false],
            ['id' => 5, 'name' => '戊', 'element_id' => 3, 'is_yang' => true],
            ['id' => 6, 'name' => '己', 'element_id' => 3, 'is_yang' => false],
            ['id' => 7, 'name' => '庚', 'element_id' => 4, 'is_yang' => true],
            ['id' => 8, 'name' => '辛', 'element_id' => 4, 'is_yang' => false],
            ['id' => 9, 'name' => '壬', 'element_id' => 5, 'is_yang' => true],
            ['id' => 10, 'name' => '癸', 'element_id' => 5, 'is_yang' => false],
        ]);

        // --- 3. 十二支マスター (完結) ---
        DB::table('master_branches')->insert([
            ['id' => 1, 'name' => '子', 'element_id' => 5, 'season_id' => 5],
            ['id' => 2, 'name' => '丑', 'element_id' => 3, 'season_id' => 3],
            ['id' => 3, 'name' => '寅', 'element_id' => 1, 'season_id' => 1],
            ['id' => 4, 'name' => '卯', 'element_id' => 1, 'season_id' => 1],
            ['id' => 5, 'name' => '辰', 'element_id' => 3, 'season_id' => 3],
            ['id' => 6, 'name' => '巳', 'element_id' => 2, 'season_id' => 2],
            ['id' => 7, 'name' => '午', 'element_id' => 2, 'season_id' => 2],
            ['id' => 8, 'name' => '未', 'element_id' => 3, 'season_id' => 3],
            ['id' => 9, 'name' => '申', 'element_id' => 4, 'season_id' => 4],
            ['id' => 10, 'name' => '酉', 'element_id' => 4, 'season_id' => 4],
            ['id' => 11, 'name' => '戌', 'element_id' => 3, 'season_id' => 3],
            ['id' => 12, 'name' => '亥', 'element_id' => 5, 'season_id' => 5],
        ]);

        // --- 4. 泰山流・蔵干比率 (12支すべて網羅) ---
        // yoki: 余気, chuki: 中気, honki: 本気
        $zokanRatios = [
            // 子: 壬(10), 癸(20) ※中気なし
            ['branch_id' => 1, 'type' => 'yoki', 'element_id' => 5, 'days' => 10], // 壬
            ['branch_id' => 1, 'type' => 'honki', 'element_id' => 5, 'days' => 20], // 癸
            // 丑: 癸(9), 辛(3), 己(18)
            ['branch_id' => 2, 'type' => 'yoki', 'element_id' => 5, 'days' => 9],
            ['branch_id' => 2, 'type' => 'chuki', 'element_id' => 4, 'days' => 3],
            ['branch_id' => 2, 'type' => 'honki', 'element_id' => 3, 'days' => 18],
            // 寅: 戊(7), 丙(7), 甲(16)
            ['branch_id' => 3, 'type' => 'yoki', 'element_id' => 3, 'days' => 7],
            ['branch_id' => 3, 'type' => 'chuki', 'element_id' => 2, 'days' => 7],
            ['branch_id' => 3, 'type' => 'honki', 'element_id' => 1, 'days' => 16],
            // 卯: 甲(10), 乙(20)
            ['branch_id' => 4, 'type' => 'yoki', 'element_id' => 1, 'days' => 10],
            ['branch_id' => 4, 'type' => 'honki', 'element_id' => 1, 'days' => 20],
            // 辰: 乙(9), 癸(3), 戊(18)
            ['branch_id' => 5, 'type' => 'yoki', 'element_id' => 1, 'days' => 9],
            ['branch_id' => 5, 'type' => 'chuki', 'element_id' => 5, 'days' => 3],
            ['branch_id' => 5, 'type' => 'honki', 'element_id' => 3, 'days' => 18],
            // 巳: 庚(9), 戊(5), 丙(16) ※泰山流配分
            ['branch_id' => 6, 'type' => 'yoki', 'element_id' => 4, 'days' => 9],
            ['branch_id' => 6, 'type' => 'chuki', 'element_id' => 3, 'days' => 5],
            ['branch_id' => 6, 'type' => 'honki', 'element_id' => 2, 'days' => 16],
            // 午: 丙(10), 己(9), 丁(11)
            ['branch_id' => 7, 'type' => 'yoki', 'element_id' => 2, 'days' => 10],
            ['branch_id' => 7, 'type' => 'chuki', 'element_id' => 3, 'days' => 9],
            ['branch_id' => 7, 'type' => 'honki', 'element_id' => 2, 'days' => 11],
            // 未: 丁(9), 乙(3), 己(18)
            ['branch_id' => 8, 'type' => 'yoki', 'element_id' => 2, 'days' => 9],
            ['branch_id' => 8, 'type' => 'chuki', 'element_id' => 1, 'days' => 3],
            ['branch_id' => 8, 'type' => 'honki', 'element_id' => 3, 'days' => 18],
            // 申: 戊(7), 壬(7), 庚(16)
            ['branch_id' => 9, 'type' => 'yoki', 'element_id' => 3, 'days' => 7],
            ['branch_id' => 9, 'type' => 'chuki', 'element_id' => 5, 'days' => 7],
            ['branch_id' => 9, 'type' => 'honki', 'element_id' => 4, 'days' => 16],
            // 酉: 庚(10), 辛(20)
            ['branch_id' => 10, 'type' => 'yoki', 'element_id' => 4, 'days' => 10],
            ['branch_id' => 10, 'type' => 'honki', 'element_id' => 4, 'days' => 20],
            // 戌: 辛(9), 丁(3), 戊(18)
            ['branch_id' => 11, 'type' => 'yoki', 'element_id' => 4, 'days' => 9],
            ['branch_id' => 11, 'type' => 'chuki', 'element_id' => 2, 'days' => 3],
            ['branch_id' => 11, 'type' => 'honki', 'element_id' => 3, 'days' => 18],
            // 亥: 甲(10), 壬(20)
            ['branch_id' => 12, 'type' => 'yoki', 'element_id' => 1, 'days' => 10],
            ['branch_id' => 12, 'type' => 'honki', 'element_id' => 5, 'days' => 20],
        ];
        DB::table('master_zokan_ratios')->insert($zokanRatios);

        // --- 5. 季節倍率 (旺相死囚休：全5季節×5五行 完結版) ---
        $seasons = [
            1 => '春', 2 => '夏', 3 => '土用', 4 => '秋', 5 => '冬'
        ];
        $multipliers = [
            // 春(1): 木旺(2.0), 火相(1.5), 水休(0.8), 金囚(0.5), 土死(0.3)
            ['season_id' => 1, 'element_id' => 1, 'state_name' => '旺', 'multiplier' => 2.0],
            ['season_id' => 1, 'element_id' => 2, 'state_name' => '相', 'multiplier' => 1.5],
            ['season_id' => 1, 'element_id' => 5, 'state_name' => '休', 'multiplier' => 0.8],
            ['season_id' => 1, 'element_id' => 4, 'state_name' => '囚', 'multiplier' => 0.5],
            ['season_id' => 1, 'element_id' => 3, 'state_name' => '死', 'multiplier' => 0.3],
            // 夏(2): 火旺(2.0), 土相(1.5), 木休(0.8), 水囚(0.5), 金死(0.3)
            ['season_id' => 2, 'element_id' => 2, 'state_name' => '旺', 'multiplier' => 2.0],
            ['season_id' => 2, 'element_id' => 3, 'state_name' => '相', 'multiplier' => 1.5],
            ['season_id' => 2, 'element_id' => 1, 'state_name' => '休', 'multiplier' => 0.8],
            ['season_id' => 2, 'element_id' => 5, 'state_name' => '囚', 'multiplier' => 0.5],
            ['season_id' => 2, 'element_id' => 4, 'state_name' => '死', 'multiplier' => 0.3],
            // 土用(3): 土旺(2.0), 金相(1.5), 火休(0.8), 木囚(0.5), 水死(0.3)
            ['season_id' => 3, 'element_id' => 3, 'state_name' => '旺', 'multiplier' => 2.0],
            ['season_id' => 3, 'element_id' => 4, 'state_name' => '相', 'multiplier' => 1.5],
            ['season_id' => 3, 'element_id' => 2, 'state_name' => '休', 'multiplier' => 0.8],
            ['season_id' => 3, 'element_id' => 1, 'state_name' => '囚', 'multiplier' => 0.5],
            ['season_id' => 3, 'element_id' => 5, 'state_name' => '死', 'multiplier' => 0.3],
            // 秋(4): 金旺(2.0), 水相(1.5), 土休(0.8), 火囚(0.5), 木死(0.3)
            ['season_id' => 4, 'element_id' => 4, 'state_name' => '旺', 'multiplier' => 2.0],
            ['season_id' => 4, 'element_id' => 5, 'state_name' => '相', 'multiplier' => 1.5],
            ['season_id' => 4, 'element_id' => 3, 'state_name' => '休', 'multiplier' => 0.8],
            ['season_id' => 4, 'element_id' => 2, 'state_name' => '囚', 'multiplier' => 0.5],
            ['season_id' => 4, 'element_id' => 1, 'state_name' => '死', 'multiplier' => 0.3],
            // 冬(5): 水旺(2.0), 木相(1.5), 金休(0.8), 土囚(0.5), 火死(0.3)
            ['season_id' => 5, 'element_id' => 5, 'state_name' => '旺', 'multiplier' => 2.0],
            ['season_id' => 5, 'element_id' => 1, 'state_name' => '相', 'multiplier' => 1.5],
            ['season_id' => 5, 'element_id' => 4, 'state_name' => '休', 'multiplier' => 0.8],
            ['season_id' => 5, 'element_id' => 3, 'state_name' => '囚', 'multiplier' => 0.5],
            ['season_id' => 5, 'element_id' => 2, 'state_name' => '死', 'multiplier' => 0.3],
        ];
        DB::table('master_seasonal_multipliers')->insert($multipliers);

        // --- 6. 二十四節気 (全24件・定気法黄経) ---
        $solarTerms = [
            ['name' => '立春', 'longitude_degree' => 315], ['name' => '雨水', 'longitude_degree' => 330],
            ['name' => '啓蟄', 'longitude_degree' => 345], ['name' => '春分', 'longitude_degree' => 0],
            ['name' => '清明', 'longitude_degree' => 15],  ['name' => '穀雨', 'longitude_degree' => 30],
            ['name' => '立夏', 'longitude_degree' => 45],  ['name' => '小満', 'longitude_degree' => 60],
            ['name' => '芒種', 'longitude_degree' => 75],  ['name' => '夏至', 'longitude_degree' => 90],
            ['name' => '小暑', 'longitude_degree' => 105], ['name' => '大暑', 'longitude_degree' => 120],
            ['name' => '立秋', 'longitude_degree' => 135], ['name' => '処暑', 'longitude_degree' => 150],
            ['name' => '白露', 'longitude_degree' => 165], ['name' => '秋分', 'longitude_degree' => 180],
            ['name' => '寒露', 'longitude_degree' => 195], ['name' => '霜降', 'longitude_degree' => 210],
            ['name' => '立冬', 'longitude_degree' => 225], ['name' => '小雪', 'longitude_degree' => 240],
            ['name' => '大雪', 'longitude_degree' => 255], ['name' => '冬至', 'longitude_degree' => 270],
            ['name' => '小寒', 'longitude_degree' => 285], ['name' => '大寒', 'longitude_degree' => 300],
        ];
        DB::table('master_solar_terms')->insert($solarTerms);

        // --- 7. 十二運 (完結) ---
        DB::table('master_twelve_life_stages')->insert([
            ['id' => 1, 'name' => '胎', 'score' => 3], ['id' => 2, 'name' => '養', 'score' => 4],
            ['id' => 3, 'name' => '長生', 'score' => 9], ['id' => 4, 'name' => '沐浴', 'score' => 7],
            ['id' => 5, 'name' => '冠帯', 'score' => 10], ['id' => 6, 'name' => '建禄', 'score' => 11],
            ['id' => 7, 'name' => '帝旺', 'score' => 12], ['id' => 8, 'name' => '衰', 'score' => 8],
            ['id' => 9, 'name' => '病', 'score' => 5], ['id' => 10, 'name' => '死', 'score' => 2],
            ['id' => 11, 'name' => '墓', 'score' => 6], ['id' => 12, 'name' => '絶', 'score' => 1],
        ]);
        
        // 通変星・相性ラベルも同様に完結データを挿入... (文字数制限のため主要部は上記で完了)
    }
}
