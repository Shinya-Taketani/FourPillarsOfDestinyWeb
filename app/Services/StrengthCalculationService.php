<?php

declare(strict_types=1);

namespace App\Services;

/**
 * 既存の五行スコア配列を維持するための互換アダプター。
 */
readonly class StrengthCalculationService
{
    public function __construct(
        private FiveElementStrengthService $fiveElementStrengthService,
    ) {}

    /**
     * 力量スコアの算出
     */
    public function calculate(array $pillars, int $monthBranchId): array
    {
        $strength = $this->fiveElementStrengthService->calculate($pillars, $monthBranchId);

        return $this->fiveElementStrengthService->compatibilityScores($strength);
    }
}
