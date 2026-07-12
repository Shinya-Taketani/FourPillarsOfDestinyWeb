<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SolarTermDefinitionSeeder extends Seeder
{
    public function run(): void
    {
        $branchIds = DB::table('master_branches')->pluck('id', 'name');
        $now = now();

        $terms = [
            ['name' => '小寒', 'longitude_degree' => 285, 'branch' => '丑'],
            ['name' => '立春', 'longitude_degree' => 315, 'branch' => '寅'],
            ['name' => '啓蟄', 'longitude_degree' => 345, 'branch' => '卯'],
            ['name' => '清明', 'longitude_degree' => 15, 'branch' => '辰'],
            ['name' => '立夏', 'longitude_degree' => 45, 'branch' => '巳'],
            ['name' => '芒種', 'longitude_degree' => 75, 'branch' => '午'],
            ['name' => '小暑', 'longitude_degree' => 105, 'branch' => '未'],
            ['name' => '立秋', 'longitude_degree' => 135, 'branch' => '申'],
            ['name' => '白露', 'longitude_degree' => 165, 'branch' => '酉'],
            ['name' => '寒露', 'longitude_degree' => 195, 'branch' => '戌'],
            ['name' => '立冬', 'longitude_degree' => 225, 'branch' => '亥'],
            ['name' => '大雪', 'longitude_degree' => 255, 'branch' => '子'],
        ];

        foreach ($terms as $index => $term) {
            DB::table('solar_term_definitions')->updateOrInsert(
                ['name' => $term['name']],
                [
                    'longitude_degree' => $term['longitude_degree'],
                    'term_type' => 'major_term',
                    'month_branch_id' => $branchIds[$term['branch']],
                    'display_order' => $index + 1,
                    'is_month_boundary' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }
    }
}
