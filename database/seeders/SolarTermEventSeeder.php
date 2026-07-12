<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SolarTermEventSeeder extends Seeder
{
    public function run(): void
    {
        $definitionIds = DB::table('solar_term_definitions')->pluck('id', 'name');
        $now = now();

        $sourceTitle = '国立天文台 令和8年(2026)暦要項 二十四節気および雑節';
        $sourceUrl = 'https://eco.mtk.nao.ac.jp/koyomi/yoko/2026/rekiyou262.html';
        $note = '四柱推命の年柱・月柱境界検証用に採用する公的節入り時刻。天保壬寅元暦そのものの実装ではない。';

        $events = [
            ['name' => '小寒', 'started_at' => '2026-01-05 17:23:00'],
            ['name' => '立春', 'started_at' => '2026-02-04 05:02:00'],
            ['name' => '啓蟄', 'started_at' => '2026-03-05 22:59:00'],
            ['name' => '清明', 'started_at' => '2026-04-05 03:40:00'],
            ['name' => '立夏', 'started_at' => '2026-05-05 20:49:00'],
            ['name' => '芒種', 'started_at' => '2026-06-06 00:48:00'],
            ['name' => '小暑', 'started_at' => '2026-07-07 10:57:00'],
            ['name' => '立秋', 'started_at' => '2026-08-07 20:43:00'],
            ['name' => '白露', 'started_at' => '2026-09-07 23:41:00'],
            ['name' => '寒露', 'started_at' => '2026-10-08 15:29:00'],
            ['name' => '立冬', 'started_at' => '2026-11-07 18:52:00'],
            ['name' => '大雪', 'started_at' => '2026-12-07 11:53:00'],
        ];

        foreach ($events as $event) {
            DB::table('solar_term_events')->updateOrInsert(
                [
                    'year' => 2026,
                    'solar_term_definition_id' => $definitionIds[$event['name']],
                    'source_rank' => 'S',
                ],
                [
                    'started_at' => $event['started_at'],
                    'timezone' => 'Asia/Tokyo',
                    'calendar_system' => 'modern_astronomical',
                    'source_title' => $sourceTitle,
                    'source_url' => $sourceUrl,
                    'adopted' => true,
                    'note' => $note,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }
    }
}
