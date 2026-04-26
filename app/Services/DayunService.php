<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

readonly class DayunService
{
    public function __construct(private StarCalculationService $starService) {}

    public function calculate(array $yearPillar, array $monthPillar, int $dayStemId, CarbonImmutable $lmt, array $solar, string $gender): array
    {
        // 1. 順行・逆行の判定
        $isYearYang = ($yearPillar['stem_id'] % 2 !== 0);
        $isForward = ($gender === 'male') ? $isYearYang : !$isYearYang;

        // 2. 精密な立運（開始年齢）の計算
        // $solar['started_at'] は「その月の開始」
        $birthTermStart = CarbonImmutable::parse($solar['started_at']);
        
        if ($isForward) {
            // 順行：誕生日から「次の節入り」まで
            // 検索範囲を誕生日の35日以内に限定し、2026年へのジャンプを完全に阻止
            $nextTerm = DB::table('master_solar_terms')
                ->where('started_at', '>', $lmt->toDateTimeString())
                ->where('started_at', '<', $lmt->addDays(35)->toDateTimeString())
                ->orderBy('started_at', 'asc')
                ->first();
            
            // 万が一データがない場合でも、平均値（30.44日）で計算し、異常値を防ぐ
            $targetDate = $nextTerm ? CarbonImmutable::parse($nextTerm->started_at) : $lmt->addDays(30);
            $diffSeconds = $lmt->diffInSeconds($targetDate);
        } else {
            // 逆行：誕生日から「その月の節入り（開始）」まで遡る
            $diffSeconds = $birthTermStart->diffInSeconds($lmt);
        }

        /**
         * 泰山流・精密立運算出
         * 3日(259200秒) = 1年
         * 1日(86400秒) = 4ヶ月 (1ヶ月 = 21600秒)
         * 1時間(3600秒) = 5日 (1日 = 720秒)
         */
        $years = (int)($diffSeconds / 259200);
        $rem = $diffSeconds % 259200;
        
        $months = (int)($rem / 21600);
        $rem = $rem % 21600;
        
        $days = (int)($rem / 720);

        $startAgeFull = "{$years}歳 {$months}ヶ月 {$days}日";
        // リスト表示用：6ヶ月以上なら切り上げ（実務慣習）
        $startAgeInt = ($months >= 6) ? $years + 1 : $years;
        $startAgeInt = (int)max(1, $startAgeInt);

        // 3. 10期分の大運干支を生成
        $cycles = [];
        $currentStemId = $monthPillar['stem_id'];
        $currentBranchId = $monthPillar['branch_id'];

        for ($i = 0; $i < 10; $i++) {
            if ($isForward) {
                $currentStemId = ($currentStemId % 10) + 1;
                $currentBranchId = ($currentBranchId % 12) + 1;
            } else {
                $currentStemId = ($currentStemId === 1) ? 10 : $currentStemId - 1;
                $currentBranchId = ($currentBranchId === 1) ? 12 : $currentBranchId - 1;
            }

            $stemName = DB::table('master_stems')->where('id', $currentStemId)->value('name');
            $branchName = DB::table('master_branches')->where('id', $currentBranchId)->value('name');

            $cycles[] = [
                'age' => $startAgeInt + ($i * 10),
                'kanji' => ($stemName ?? '') . ($branchName ?? ''),
                'ten_god' => $this->starService->getTenGod($dayStemId, $currentStemId),
            ];
        }

        return [
            'is_forward' => $isForward,
            'start_age' => $startAgeInt,
            'start_age_full' => $startAgeFull,
            'cycles' => $cycles,
        ];
    }
}
