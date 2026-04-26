<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SolarTermSeeder2 extends Seeder
{
    public function run()
    {
        // 既存データを完全にクリア
        DB::table('master_solar_terms')->truncate();

        // 節入り基準データ
        $terms = [
            ['name' => '小寒', 'month' => 1, 'base_day' => 6.1101, 'degree' => 285, 'branch' => 2], // 丑
            ['name' => '立春', 'month' => 2, 'base_day' => 4.6293, 'degree' => 315, 'branch' => 3], // 寅
            ['name' => '啓蟄', 'month' => 3, 'base_day' => 6.3826, 'degree' => 345, 'branch' => 4], // 卯
            ['name' => '清明', 'month' => 4, 'base_day' => 5.5943, 'degree' => 15,  'branch' => 5], // 辰
            ['name' => '立夏', 'month' => 5, 'base_day' => 6.1154, 'degree' => 45,  'branch' => 6], // 巳
            ['name' => '芒種', 'month' => 6, 'base_day' => 6.5003, 'degree' => 75,  'branch' => 7], // 午
            ['name' => '小暑', 'month' => 7, 'base_day' => 7.9281, 'degree' => 105, 'branch' => 8], // 未
            ['name' => '立秋', 'month' => 8, 'base_day' => 8.3501, 'degree' => 135, 'branch' => 9], // 申
            ['name' => '白露', 'month' => 9, 'base_day' => 8.4435, 'degree' => 165, 'branch' => 10],// 酉
            ['name' => '寒露', 'month' => 10, 'base_day' => 9.0987, 'degree' => 195, 'branch' => 11],// 戌
            ['name' => '立冬', 'month' => 11, 'base_day' => 8.2191, 'degree' => 225, 'branch' => 12],// 亥
            ['name' => '大雪', 'month' => 12, 'base_day' => 7.9221, 'degree' => 255, 'branch' => 1], // 子
        ];

        $data = [];
        for ($year = 1900; $year <= 2100; $year++) {
            // 年干の計算 (1:甲...10:癸)
            $yearStemId = (($year - 4) % 10) + 1;
            if ($yearStemId <= 0) $yearStemId += 10;

            foreach ($terms as $t) {
                // 精密な節入り時刻の算出
                $y = $year - 1900;
                $days = $t['base_day'] + (0.242194 * $y) - (int)($y / 4);
                $d = (int)$days;
                $hourDecimal = ($days - $d) * 24;
                $h = (int)$hourDecimal;
                $m = (int)(($hourDecimal - $h) * 60);

                $dt = Carbon::create($year, $t['month'], $d, $h, $m, 0);

                // 月干の計算（五虎遁法）
                // 1月(丑)と2月(寅)で年が変わる境界を考慮（立春基準）
                $calcYearStemId = ($t['month'] == 1) ? ((($year - 1 - 4) % 10) + 1) : $yearStemId;
                if ($calcYearStemId <= 0) $calcYearStemId += 10;

                // 寅月の天干を求める
                $startStemId = (($calcYearStemId - 1) % 5) * 2 + 3;
                if ($startStemId > 10) $startStemId -= 10;

                // 各月の天干を求める
                // 寅(3)から数えて何番目か
                $offset = $t['branch'] - 3;
                if ($offset < 0) $offset += 12;
                $monthStemId = (($startStemId + $offset - 1) % 10) + 1;

                // マイレーションのカラム名に厳密に合わせる
                $data[] = [
                    'name'             => $t['name'],
                    'started_at'       => $dt->toDateTimeString(),
                    'longitude_degree' => $t['degree'],
                    'month_stem_id'    => $monthStemId,
                    'month_branch_id'  => $t['branch'],
                ];

                if (count($data) >= 200) {
                    DB::table('master_solar_terms')->insert($data);
                    $data = [];
                }
            }
        }
        if (!empty($data)) {
            DB::table('master_solar_terms')->insert($data);
        }
    }
}
