<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\CarbonImmutable;

/**
 * 干支（十干・十二支）計算サービス
 * 西暦・日数から正確な干支番号を導き出す
 */
readonly class SexagenaryService
{
    /**
     * 年の干支を算出
     */
    public function getYearPillar(CarbonImmutable $date, string $solarTerm): array
    {
        $year = $date->year;
        // 泰山流（立春基準）：1月〜立春前日までは前年として扱う
        // ※SolarTermServiceの結果を反映させる
        if ($date->month < 2 || ($date->month == 2 && $solarTerm !== '立春' && $date->day < 4)) {
            $year--;
        }

        // 年干支番号の計算 (2026年 = 丙午 = 43番目)
        // (Year - 3) % 60
        $index = ($year - 3) % 60;
        if ($index <= 0) $index += 60;

        return $this->splitIndex($index);
    }

    /**
     * 日の干支を算出（もっとも重要な自分自身の星）
     */
    public function getDayPillar(CarbonImmutable $date): array
    {
        // 基準日（1900年1月31日が 1:甲子）からの経過日数で算出
        $baseDate = CarbonImmutable::create(1900, 1, 31);
        $diffDays = $baseDate->diffInDays($date);
        
        $index = ($diffDays % 60) + 1;

        return $this->splitIndex((int)$index);
    }

    /**
     * 時の干支を算出（日干のIDによって時干が決まる）
     */
    public function getHourPillar(CarbonImmutable $date, int $dayStemId): array
    {
        // 時支は2時間おきに決まる (23-1時: 子, 1-3時: 丑...)
        $hour = $date->hour;
        $branchId = (int)(($hour + 1) / 2) % 12 + 1;

        // 時干の決定（甲己日→甲子時、乙庚日→丙子時...の法則）
        $baseStemByDay = [
            1 => 1, 6 => 1, // 甲・己日は甲から
            2 => 3, 7 => 3, // 乙・庚日は丙から
            3 => 5, 8 => 5, // 丙・辛日は戊から
            4 => 7, 9 => 7, // 丁・壬日は庚から
            5 => 9, 10 => 9, // 戊・癸日は壬から
        ];
        
        $startStemId = $baseStemByDay[$dayStemId] ?? 1;
        $stemId = ($startStemId + $branchId - 2) % 10 + 1;

        return ['stem_id' => $stemId, 'branch_id' => $branchId];
    }

    /**
     * 1〜60の番号を十干(1-10)と十二支(1-12)に分解
     */
    private function splitIndex(int $index): array
    {
        $stemId = $index % 10;
        if ($stemId === 0) $stemId = 10;

        $branchId = $index % 12;
        if ($branchId === 0) $branchId = 12;

        return ['stem_id' => $stemId, 'branch_id' => $branchId];
    }
}
