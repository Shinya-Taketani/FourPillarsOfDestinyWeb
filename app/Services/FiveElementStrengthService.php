<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\MasterDataRepository;
use Illuminate\Support\Collection;
use RuntimeException;

readonly class FiveElementStrengthService
{
    private const PILLAR_NAMES = ['year', 'month', 'day', 'hour'];

    private const HEAVENLY_STEM_WEIGHT = 1.0;

    private const HIDDEN_STEM_DAYS_BASE = 30.0;

    public function __construct(
        private MasterDataRepository $masterData,
    ) {}

    /**
     * 現行配点を保ちながら、五行力量の計算材料と季節補正を分離して返す。
     */
    public function calculate(array $pillars, int $monthBranchId): array
    {
        $elements = $this->masterData->elementsById();
        $stems = $this->masterData->stemsById();
        $branches = $this->masterData->branchesById();
        $ratios = $this->masterData->zokanRatiosByBranchId();

        if ($elements->count() !== 5) {
            throw new RuntimeException('Five element masters are incomplete.');
        }

        $componentScores = [
            'heavenly_stems' => $this->emptyScores($elements),
            // 現行計算では地支を別加点せず、蔵干の日数比で地支の力量を表現する。
            'earthly_branches' => $this->emptyScores($elements),
            'hidden_stems' => $this->emptyScores($elements),
        ];
        $basis = [
            'heavenly_stems' => [],
            'earthly_branches' => [],
            'hidden_stems' => [],
            'seasonal_multiplier' => [],
            'notes' => [],
        ];

        foreach (self::PILLAR_NAMES as $pillarName) {
            $pillar = $pillars[$pillarName] ?? null;
            if (! is_array($pillar)) {
                throw new RuntimeException("Pillar data is missing for {$pillarName}.");
            }

            $stemId = (int) ($pillar['stem_id'] ?? 0);
            $branchId = (int) ($pillar['branch_id'] ?? 0);
            $stem = $stems->get($stemId);
            $branch = $branches->get($branchId);

            if ($stem === null || $branch === null) {
                throw new RuntimeException("Stem or branch master is missing for {$pillarName} pillar.");
            }

            $stemElement = $this->element($elements, (int) $stem->element_id);
            $branchElement = $this->element($elements, (int) $branch->element_id);
            $componentScores['heavenly_stems'][$stemElement->name] += self::HEAVENLY_STEM_WEIGHT;
            $basis['heavenly_stems'][] = [
                'pillar' => $pillarName,
                'stem_id' => $stemId,
                'stem' => $stem->name,
                'element_id' => (int) $stemElement->id,
                'element' => $stemElement->name,
                'weight' => self::HEAVENLY_STEM_WEIGHT,
            ];
            $basis['earthly_branches'][] = [
                'pillar' => $pillarName,
                'branch_id' => $branchId,
                'branch' => $branch->name,
                'element_id' => (int) $branchElement->id,
                'element' => $branchElement->name,
                'weight' => 0.0,
                'score_applied' => false,
                'note' => '既存計算との互換性のため地支本体は別加点せず、蔵干日数比で集計する。配点根拠は要確認。',
            ];

            $branchRatios = $ratios->get($branchId, collect());
            if ($branchRatios->isEmpty()) {
                $basis['notes'][] = "branch_id {$branchId} の蔵干比率が未登録。";
            }

            foreach ($branchRatios as $ratio) {
                if (! isset($ratio->stem_id) || $ratio->stem_id === null) {
                    throw new RuntimeException("Zokan stem_id is not set for branch_id {$branchId}.");
                }

                $hiddenStem = $stems->get((int) $ratio->stem_id);
                if ($hiddenStem === null) {
                    throw new RuntimeException("Zokan stem master is missing for stem_id {$ratio->stem_id}.");
                }

                $hiddenElement = $this->element($elements, (int) $hiddenStem->element_id);
                $weight = round((int) $ratio->days / self::HIDDEN_STEM_DAYS_BASE, 6);
                $componentScores['hidden_stems'][$hiddenElement->name] += $weight;
                $basis['hidden_stems'][] = [
                    'pillar' => $pillarName,
                    'branch_id' => $branchId,
                    'type' => $ratio->type,
                    'stem_id' => (int) $hiddenStem->id,
                    'stem' => $hiddenStem->name,
                    'element_id' => (int) $hiddenElement->id,
                    'element' => $hiddenElement->name,
                    'days' => (int) $ratio->days,
                    'weight' => $weight,
                ];
            }
        }

        $rawScores = $this->sumScores($componentScores, $elements);
        $seasonal = $this->seasonalAdjustment($rawScores, $monthBranchId, $branches, $elements);
        $basis['seasonal_multiplier'] = $seasonal['basis'];

        return [
            'status' => 'pending',
            'raw_scores' => $rawScores,
            'seasonal_adjusted_scores' => $seasonal['scores'],
            'normalized_scores' => $this->normalize($seasonal['scores']),
            'seasonal_status' => $seasonal['statuses'],
            'component_scores' => $componentScores,
            'seasonal_adjustment' => $seasonal['meta'],
            'basis' => $basis,
            'source_rank' => 'PENDING',
            'source_note' => '天干1.0・蔵干days/30の配点と旺相死囚休の季節倍率は既存実装を構造化した検証用データ。泰山流資料で要確認。',
        ];
    }

    /**
     * 既存 Vue / API 用の配列形式へ変換する。
     */
    public function compatibilityScores(array $strength): array
    {
        $scores = $strength['seasonal_adjusted_scores'] ?? $strength['raw_scores'] ?? [];
        $elements = $this->masterData->elementsById();

        return $elements->map(fn ($element) => [
            'element' => $element->name,
            'score' => (float) ($scores[$element->name] ?? 0.0),
            'color' => $element->color_code,
        ])->values()->all();
    }

    private function seasonalAdjustment(
        array $rawScores,
        int $monthBranchId,
        Collection $branches,
        Collection $elements,
    ): array {
        $monthBranch = $branches->get($monthBranchId);
        if ($monthBranch === null) {
            throw new RuntimeException("Month branch master is missing for branch_id {$monthBranchId}.");
        }

        $seasonId = (int) $monthBranch->season_id;
        $multipliers = $this->masterData->seasonalMultipliersByMonthBranchId($monthBranchId)
            ->keyBy('element_id');
        $complete = $elements->keys()->every(fn ($elementId) => $multipliers->has($elementId));
        $scores = [];
        $statuses = [];
        $basis = [];

        foreach ($elements as $element) {
            $row = $multipliers->get($element->id);
            $multiplier = $row === null ? null : (float) $row->multiplier;
            $statuses[$element->name] = $row->state_name ?? 'PENDING';
            $scores[$element->name] = $complete
                ? round($rawScores[$element->name] * $multiplier, 2)
                : (float) $rawScores[$element->name];
            $basis[$element->name] = [
                'element_id' => (int) $element->id,
                'state' => $row->state_name ?? 'PENDING',
                'multiplier' => $multiplier,
            ];
        }

        return [
            'scores' => $scores,
            'statuses' => $statuses,
            'basis' => $basis,
            'meta' => [
                'status' => $complete ? 'applied_pending_verification' : 'pending',
                'applied' => $complete,
                'month_branch_id' => $monthBranchId,
                'month_branch' => $monthBranch->name,
                'season_id' => $seasonId,
                'source_rank' => 'PENDING',
                'source_note' => $complete
                    ? '登録済み倍率を適用。倍率の出典と泰山流での採用可否は要確認。'
                    : '季節倍率が5五行分そろっていないため補正未適用。要確認。',
            ],
        ];
    }

    private function emptyScores(Collection $elements): array
    {
        return $elements->mapWithKeys(fn ($element) => [$element->name => 0.0])->all();
    }

    private function sumScores(array $componentScores, Collection $elements): array
    {
        $scores = $this->emptyScores($elements);

        foreach ($componentScores as $component) {
            foreach ($component as $element => $score) {
                $scores[$element] += $score;
            }
        }

        return array_map(fn ($score) => round((float) $score, 6), $scores);
    }

    private function normalize(array $scores): array
    {
        $total = array_sum($scores);

        return array_map(
            fn ($score) => $total > 0 ? round(((float) $score / $total) * 100, 2) : 0.0,
            $scores,
        );
    }

    private function element(Collection $elements, int $elementId): object
    {
        $element = $elements->get($elementId);
        if ($element === null) {
            throw new RuntimeException("Element master is missing for element_id {$elementId}.");
        }

        return $element;
    }
}
