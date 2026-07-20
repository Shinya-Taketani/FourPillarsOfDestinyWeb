<?php

declare(strict_types=1);

namespace App\Support;

final class SolarTermCatalog
{
    /**
     * @var list<array{name:string,longitude_degree:int,term_type:string,is_month_boundary:bool,branch:?string}>
     */
    public const TERMS = [
        ['name' => '小寒', 'longitude_degree' => 285, 'term_type' => 'major_term', 'is_month_boundary' => true, 'branch' => '丑'],
        ['name' => '大寒', 'longitude_degree' => 300, 'term_type' => 'middle_term', 'is_month_boundary' => false, 'branch' => null],
        ['name' => '立春', 'longitude_degree' => 315, 'term_type' => 'major_term', 'is_month_boundary' => true, 'branch' => '寅'],
        ['name' => '雨水', 'longitude_degree' => 330, 'term_type' => 'middle_term', 'is_month_boundary' => false, 'branch' => null],
        ['name' => '啓蟄', 'longitude_degree' => 345, 'term_type' => 'major_term', 'is_month_boundary' => true, 'branch' => '卯'],
        ['name' => '春分', 'longitude_degree' => 0, 'term_type' => 'middle_term', 'is_month_boundary' => false, 'branch' => null],
        ['name' => '清明', 'longitude_degree' => 15, 'term_type' => 'major_term', 'is_month_boundary' => true, 'branch' => '辰'],
        ['name' => '穀雨', 'longitude_degree' => 30, 'term_type' => 'middle_term', 'is_month_boundary' => false, 'branch' => null],
        ['name' => '立夏', 'longitude_degree' => 45, 'term_type' => 'major_term', 'is_month_boundary' => true, 'branch' => '巳'],
        ['name' => '小満', 'longitude_degree' => 60, 'term_type' => 'middle_term', 'is_month_boundary' => false, 'branch' => null],
        ['name' => '芒種', 'longitude_degree' => 75, 'term_type' => 'major_term', 'is_month_boundary' => true, 'branch' => '午'],
        ['name' => '夏至', 'longitude_degree' => 90, 'term_type' => 'middle_term', 'is_month_boundary' => false, 'branch' => null],
        ['name' => '小暑', 'longitude_degree' => 105, 'term_type' => 'major_term', 'is_month_boundary' => true, 'branch' => '未'],
        ['name' => '大暑', 'longitude_degree' => 120, 'term_type' => 'middle_term', 'is_month_boundary' => false, 'branch' => null],
        ['name' => '立秋', 'longitude_degree' => 135, 'term_type' => 'major_term', 'is_month_boundary' => true, 'branch' => '申'],
        ['name' => '処暑', 'longitude_degree' => 150, 'term_type' => 'middle_term', 'is_month_boundary' => false, 'branch' => null],
        ['name' => '白露', 'longitude_degree' => 165, 'term_type' => 'major_term', 'is_month_boundary' => true, 'branch' => '酉'],
        ['name' => '秋分', 'longitude_degree' => 180, 'term_type' => 'middle_term', 'is_month_boundary' => false, 'branch' => null],
        ['name' => '寒露', 'longitude_degree' => 195, 'term_type' => 'major_term', 'is_month_boundary' => true, 'branch' => '戌'],
        ['name' => '霜降', 'longitude_degree' => 210, 'term_type' => 'middle_term', 'is_month_boundary' => false, 'branch' => null],
        ['name' => '立冬', 'longitude_degree' => 225, 'term_type' => 'major_term', 'is_month_boundary' => true, 'branch' => '亥'],
        ['name' => '小雪', 'longitude_degree' => 240, 'term_type' => 'middle_term', 'is_month_boundary' => false, 'branch' => null],
        ['name' => '大雪', 'longitude_degree' => 255, 'term_type' => 'major_term', 'is_month_boundary' => true, 'branch' => '子'],
        ['name' => '冬至', 'longitude_degree' => 270, 'term_type' => 'middle_term', 'is_month_boundary' => false, 'branch' => null],
    ];

    /**
     * @return array<string,array{name:string,longitude_degree:int,term_type:string,is_month_boundary:bool,branch:?string}>
     */
    public static function indexedByName(): array
    {
        $terms = [];

        foreach (self::TERMS as $term) {
            $terms[$term['name']] = $term;
        }

        return $terms;
    }
}
