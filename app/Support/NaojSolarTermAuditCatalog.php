<?php

declare(strict_types=1);

namespace App\Support;

final class NaojSolarTermAuditCatalog
{
    /** @var array<int,array<string,string>> */
    public const EXPECTED_EVENTS = [
        1971 => [
            '小寒' => '1971-01-06 08:45:00',
            '立春' => '1971-02-04 20:26:00',
            '啓蟄' => '1971-03-06 14:35:00',
        ],
        1980 => [
            '小寒' => '1980-01-06 13:29:00',
            '立春' => '1980-02-05 01:10:00',
            '啓蟄' => '1980-03-05 19:17:00',
        ],
        2026 => [
            '小寒' => '2026-01-05 17:23:00',
            '立春' => '2026-02-04 05:02:00',
            '啓蟄' => '2026-03-05 22:59:00',
            '清明' => '2026-04-05 03:40:00',
            '立夏' => '2026-05-05 20:49:00',
            '芒種' => '2026-06-06 00:48:00',
            '小暑' => '2026-07-07 10:57:00',
            '立秋' => '2026-08-07 20:43:00',
            '白露' => '2026-09-07 23:41:00',
            '寒露' => '2026-10-08 15:29:00',
            '立冬' => '2026-11-07 18:52:00',
            '大雪' => '2026-12-07 11:53:00',
        ],
    ];

    /** @var array<int,string> */
    public const SOURCE_URLS = [
        1971 => 'https://eco.mtk.nao.ac.jp/koyomi/yoko/pdf/yoko1971.pdf',
        1980 => 'https://eco.mtk.nao.ac.jp/koyomi/yoko/pdf/yoko1980.pdf',
        2026 => 'https://eco.mtk.nao.ac.jp/koyomi/yoko/2026/rekiyou262.html',
    ];

    /**
     * @param  list<array{year:int,term_name:string,longitude_degree:int,started_at:string}>  $events
     * @return list<array{term_name:string,actual:string,expected:string}>
     */
    public static function mismatches(int $year, array $events): array
    {
        $expected = self::EXPECTED_EVENTS[$year] ?? [];
        $actualByName = [];

        foreach ($events as $event) {
            $actualByName[$event['term_name']] = $event['started_at'];
        }

        $mismatches = [];

        foreach ($expected as $termName => $expectedStartedAt) {
            $actual = $actualByName[$termName] ?? 'missing';

            if ($actual !== $expectedStartedAt) {
                $mismatches[] = [
                    'term_name' => $termName,
                    'actual' => $actual,
                    'expected' => $expectedStartedAt,
                ];
            }
        }

        return $mismatches;
    }
}
