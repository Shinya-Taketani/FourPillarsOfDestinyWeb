<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

readonly class SolarTermService
{
    public function getSolarInfo(CarbonImmutable $lmtDateTime): array
    {
        $term = DB::table('master_solar_terms')
            ->where('started_at', '<=', $lmtDateTime->toDateTimeString())
            ->orderBy('started_at', 'desc')
            ->first();

        // データがない場合のフォールバックにも started_at を追加
        if (!$term) {
            return [
                'term_name' => 'データ範囲外',
                'month_stem_id' => 1,
                'month_branch_id' => 3,
                'started_at' => $lmtDateTime->toDateTimeString(), // 仮の日時
            ];
        }

        return [
            'term_name' => $term->name,
            'month_stem_id' => (int)$term->month_stem_id,
            'month_branch_id' => (int)$term->month_branch_id,
            'started_at' => $term->started_at, // ← これを追加！
        ];
    }
}
