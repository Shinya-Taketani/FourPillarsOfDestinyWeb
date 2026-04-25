<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\CarbonImmutable;

/**
 * 干支計算サービス（精密版）
 */
readonly class SexagenaryService
{
    /**
     * 年の干支を算出
     * 節入り判定の結果（立春を過ぎているか）を引数で受け取る
     */
    public function getYearPillar(CarbonImmutable $date, array $solarInfo): array
    {
        $year = $date->year;

        /**
         * 泰山流：立春の「時刻」を過ぎて初めて新年となる。
         * 1月や2月の節入り前なら前年として計算する。
         */
        // DB上の最新の節入りが「立春」より前（＝まだ今年の立春に到達していない）なら前年
        // ※DB設計によりますが、ここではシンプルに判定ロジックを整理
        if ($date->month <= 2 && !str_contains($solarInfo['term_name'], '立春') && $date->month != 3) {
             // 厳密には「現在の最新の節入り」が立春のインデックスでない場合は前年
             // ここでは簡易的に「2月なのに立春データが取れていない」＝前年とみなす
             $year--;
        }

        $index = ($year - 3) % 60;
        if ($index <= 0) $index += 60;

        return $this->splitIndex($index);
    }

    public function getDayPillar(CarbonImmutable $date): array
    {
        $baseDate = CarbonImmutable::create(1900, 1, 31);
        $diffDays = $baseDate->diffInDays($date);
        $index = ($diffDays % 60) + 1;

        return $this->splitIndex((int)$index);
    }

    public function getHourPillar(CarbonImmutable $date, int $dayStemId): array
    {
        $hour = $date->hour;
        $branchId = (int)(($hour + 1) / 2) % 12 + 1;

        $baseStemByDay = [
            1 => 1, 6 => 1, 2 => 3, 7 => 3, 3 => 5, 
            8 => 5, 4 => 7, 9 => 7, 5 => 9, 10 => 9,
        ];
        
        $startStemId = $baseStemByDay[$dayStemId] ?? 1;
        $stemId = ($startStemId + $branchId - 2) % 10 + 1;

        return ['stem_id' => $stemId, 'branch_id' => $branchId];
    }

    private function splitIndex(int $index): array
    {
        $stemId = $index % 10 ?: 10;
        $branchId = $index % 12 ?: 12;
        return ['stem_id' => $stemId, 'branch_id' => $branchId];
    }
}
