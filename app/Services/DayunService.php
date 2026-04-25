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
        // 年干が陽(1,3,5,7,9)か陰(2,4,6,8,10)か
        $isYearYang = ($yearPillar['stem_id'] % 2 !== 0);
        // 男性かつ陽年、または女性かつ陰年なら「順行」
        $isForward = ($gender === 'male') ? $isYearYang : !$isYearYang;

        // 2. 立運（開始年齢）の計算 (簡易版: 3日で1歳)
        // 本来は次の節入り時刻までの正確な時間差を使いますが、ここでは日数ベースで算出
        $termDate = CarbonImmutable::parse($solar['started_at']);
        $diffDays = abs($lmt->diffInDays($termDate));
        $startAge = (int)max(1, round($diffDays / 3));

        // 3. 10年ごとの干支を8期分生成
        $cycles = [];
        $currentStemId = $monthPillar['stem_id'];
        $currentBranchId = $monthPillar['branch_id'];

        for ($i = 0; $i < 8; $i++) {
            if ($isForward) {
                $currentStemId = ($currentStemId % 10) + 1;
                $currentBranchId = ($currentBranchId % 12) + 1;
            } else {
                $currentStemId = ($currentStemId === 1) ? 10 : $currentStemId - 1;
                $currentBranchId = ($currentBranchId === 1) ? 12 : $currentBranchId - 1;
            }

            $stem = DB::table('master_stems')->where('id', $currentStemId)->first();
            $branch = DB::table('master_branches')->where('id', $currentBranchId)->first();
            $tenGod = $this->starService->getTenGod($dayStemId, $currentStemId);

            $cycles[] = [
                'age' => $startAge + ($i * 10),
                'kanji' => $stem->name . $branch->name,
                'ten_god' => $tenGod,
            ];
        }

        return [
            'is_forward' => $isForward,
            'start_age' => $startAge,
            'cycles' => $cycles,
        ];
    }
}
