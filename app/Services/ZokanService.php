<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

readonly class ZokanService
{
    /**
     * 地支と節入りからの経過日数から、蔵干（天干ID）を特定する
     */
    public function getZokanStemId(int $branchId, CarbonImmutable $lmtDateTime, ?string $solarTermStartedAt): int
    {
        // 節入り時刻が不明な場合は、各支の「本気（代表的な干）」をデフォルトで返す
        $defaults = [
            1 => 10, 2 => 6, 3 => 1, 4 => 2, 5 => 5, 6 => 3,
            7 => 4, 8 => 6, 9 => 7, 10 => 8, 11 => 5, 12 => 9
        ];

        if (!$solarTermStartedAt) return $defaults[$branchId] ?? 1;

        $startedAt = CarbonImmutable::parse($solarTermStartedAt);
        $diffDays = $startedAt->diffInDays($lmtDateTime);

        // データベースから配分を取得
        $ratios = DB::table('master_zokan_ratios')
            ->where('branch_id', $branchId)
            ->orderBy('id', 'asc')
            ->get();

        // データがDBにない場合はデフォルト値を返す
        if ($ratios->isEmpty()) return $defaults[$branchId] ?? 1;

        $currentDaysLimit = 0;
        foreach ($ratios as $ratio) {
            // プロパティが存在するかチェックしながら加算（エラー回避）
            $d = $ratio->days ?? 10;
            $currentDaysLimit += $d;

            if ($diffDays <= $currentDaysLimit) {
                return (int)($ratio->stem_id ?? $defaults[$branchId]);
            }
        }

        // 期間を過ぎた場合は最後のデータを返す
        $last = $ratios->last();
        return (int)($last->stem_id ?? $defaults[$branchId]);
    }
}
