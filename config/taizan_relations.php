<?php

return [
    'stem_combinations' => [
        ['members' => ['甲', '己'], 'transformed_element' => '土'],
        ['members' => ['乙', '庚'], 'transformed_element' => '金'],
        ['members' => ['丙', '辛'], 'transformed_element' => '水'],
        ['members' => ['丁', '壬'], 'transformed_element' => '木'],
        ['members' => ['戊', '癸'], 'transformed_element' => '火'],
    ],

    'branch_combinations' => [
        ['members' => ['子', '丑']],
        ['members' => ['寅', '亥']],
        ['members' => ['卯', '戌']],
        ['members' => ['辰', '酉']],
        ['members' => ['巳', '申']],
        ['members' => ['午', '未']],
    ],

    'branch_clashes' => [
        ['members' => ['子', '午']],
        ['members' => ['丑', '未']],
        ['members' => ['寅', '申']],
        ['members' => ['卯', '酉']],
        ['members' => ['辰', '戌']],
        ['members' => ['巳', '亥']],
    ],

    'branch_harms' => [
        ['members' => ['子', '未']],
        ['members' => ['丑', '午']],
        ['members' => ['寅', '巳']],
        ['members' => ['卯', '辰']],
        ['members' => ['申', '亥']],
        ['members' => ['酉', '戌']],
    ],

    'branch_breaks' => [
        ['members' => ['子', '酉']],
        ['members' => ['丑', '辰']],
        ['members' => ['寅', '亥']],
        ['members' => ['卯', '午']],
        ['members' => ['巳', '申']],
        ['members' => ['未', '戌']],
    ],

    'branch_punishments' => [
        'pairs' => [
            ['members' => ['子', '卯'], 'variant' => 'mutual_punishment'],
        ],
        'triples' => [
            ['members' => ['寅', '巳', '申'], 'variant' => 'three_punishment'],
            ['members' => ['丑', '未', '戌'], 'variant' => 'three_punishment'],
        ],
        // 単独存在では成立扱いにせず、命式内に同じ支が2つ以上ある場合に検出する。
        'self' => ['辰', '午', '酉', '亥'],
    ],

    'three_harmony' => [
        ['members' => ['申', '子', '辰'], 'element' => '水'],
        ['members' => ['亥', '卯', '未'], 'element' => '木'],
        ['members' => ['寅', '午', '戌'], 'element' => '火'],
        ['members' => ['巳', '酉', '丑'], 'element' => '金'],
    ],

    'directional_combinations' => [
        ['members' => ['寅', '卯', '辰'], 'element' => '木'],
        ['members' => ['巳', '午', '未'], 'element' => '火'],
        ['members' => ['申', '酉', '戌'], 'element' => '金'],
        ['members' => ['亥', '子', '丑'], 'element' => '水'],
    ],

    'metadata' => [
        'source_rank' => 'PENDING',
        'source_note' => '一般的な干支関係の検出候補。泰山流における成立条件・優先順位・化気・吉凶判断は要確認。',
    ],
];
