<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SolarTermSeeder extends Seeder
{
    public function run(): void
    {
        // 既存のデータを一度クリア（二重登録防止）
        DB::table('master_solar_terms')->truncate();

        $data = [
            // 1979年（1980年1月の判定に必要）
            ['name' => '大雪', 'started_at' => '1979-12-07 19:41:00', 'month_stem_id' => 3, 'month_branch_id' => 1, 'longitude_degree' => 255],
            
            // 1980年（庚申年）
            ['name' => '小寒', 'started_at' => '1980-01-06 07:44:00', 'month_stem_id' => 4, 'month_branch_id' => 2, 'longitude_degree' => 285],
            ['name' => '立春', 'started_at' => '1980-02-04 18:41:00', 'month_stem_id' => 5, 'month_branch_id' => 3, 'longitude_degree' => 315],
            ['name' => '啓蟄', 'started_at' => '1980-03-05 13:00:00', 'month_stem_id' => 6, 'month_branch_id' => 4, 'longitude_degree' => 345],
            ['name' => '清明', 'started_at' => '1980-04-04 23:14:00', 'month_stem_id' => 7, 'month_branch_id' => 5, 'longitude_degree' => 15],
            ['name' => '立夏', 'started_at' => '1980-05-05 10:25:00', 'month_stem_id' => 8, 'month_branch_id' => 6, 'longitude_degree' => 45],
            ['name' => '芒種', 'started_at' => '1980-06-05 22:42:00', 'month_stem_id' => 9, 'month_branch_id' => 7, 'longitude_degree' => 75],
            ['name' => '小暑', 'started_at' => '1980-07-07 09:01:00', 'month_stem_id' => 10, 'month_branch_id' => 8, 'longitude_degree' => 105],
            ['name' => '立秋', 'started_at' => '1980-08-07 18:46:00', 'month_stem_id' => 1, 'month_branch_id' => 9, 'longitude_degree' => 135],
            ['name' => '白露', 'started_at' => '1980-09-07 21:38:00', 'month_stem_id' => 2, 'month_branch_id' => 10, 'longitude_degree' => 165],
            ['name' => '寒露', 'started_at' => '1980-10-08 13:13:00', 'month_stem_id' => 3, 'month_branch_id' => 11, 'longitude_degree' => 195],
            ['name' => '立冬', 'started_at' => '1980-11-07 16:13:00', 'month_stem_id' => 4, 'month_branch_id' => 12, 'longitude_degree' => 225],
            ['name' => '大雪', 'started_at' => '1980-12-07 08:35:00', 'month_stem_id' => 5, 'month_branch_id' => 1, 'longitude_degree' => 255],
            
            // 1981年（1月の判定に必要）
            ['name' => '小寒', 'started_at' => '1981-01-05 18:55:00', 'month_stem_id' => 6, 'month_branch_id' => 2, 'longitude_degree' => 285],
        ];

        foreach ($data as $row) {
            DB::table('master_solar_terms')->insert(array_merge($row, [
                'description' => "{$row['name']}の節入りデータです。",
                // created_at と updated_at を削除しました
            ]));
        }
    }
}
