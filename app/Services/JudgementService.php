<?php

declare(strict_types=1);

namespace App\Services;

readonly class JudgementService
{
    public function evaluate(array $destiny): array
    {
        $basis = $this->basis($destiny);

        return [
            'meta' => [
                'rule_set' => config('taizan_judgement_rules.rule_set', 'taizan_pending'),
                'version' => config('taizan_judgement_rules.version', 1),
            ],
            'strength' => [
                ...$this->pendingRule('strength'),
                'label' => null,
                'candidates' => [],
                'basis' => [
                    'day_stem' => $basis['day_stem'],
                    'month_branch' => $basis['month_branch'],
                    'five_elements_scores' => $basis['five_elements_scores'],
                    'five_element_strength' => $basis['five_element_strength'],
                    'strongest_elements' => $basis['strongest_elements'],
                    'weakest_elements' => $basis['weakest_elements'],
                ],
            ],
            'pattern' => [
                ...$this->pendingRule('pattern'),
                'label' => null,
                'candidates' => [],
                'basis' => [
                    'month_pillar' => $basis['month_pillar'],
                    'month_ten_god' => $basis['month_ten_god'],
                    'visible_ten_gods' => $basis['visible_ten_gods'],
                    'zokan_ten_gods' => $basis['zokan_ten_gods'],
                ],
            ],
            'useful_god' => [
                ...$this->pendingRule('useful_god'),
                'label' => null,
                'candidates' => [],
                'basis' => [
                    'day_stem' => $basis['day_stem'],
                    'five_elements_scores' => $basis['five_elements_scores'],
                    'strongest_elements' => $basis['strongest_elements'],
                    'weakest_elements' => $basis['weakest_elements'],
                ],
            ],
            'favorable_unfavorable' => [
                ...$this->pendingRule('favorable_unfavorable'),
                'favorable' => [],
                'unfavorable' => [],
                'basis' => [
                    'day_stem' => $basis['day_stem'],
                    'five_elements_scores' => $basis['five_elements_scores'],
                    'visible_ten_gods' => $basis['visible_ten_gods'],
                ],
            ],
            'relations' => [
                'status' => 'pending',
                'items' => $basis['relations'],
                'source_rank' => config('taizan_relations.metadata.source_rank', 'PENDING'),
                'source_note' => config(
                    'taizan_relations.metadata.source_note',
                    '泰山流における関係判定の優先順位・吉凶判断は要確認。',
                ),
            ],
        ];
    }

    private function pendingRule(string $key): array
    {
        return [
            'status' => config("taizan_judgement_rules.{$key}.status", 'pending'),
            'source_rank' => config("taizan_judgement_rules.{$key}.source_rank", 'PENDING'),
            'source_note' => config("taizan_judgement_rules.{$key}.source_note", '泰山流固有判断は要確認。'),
        ];
    }

    private function basis(array $destiny): array
    {
        $pillars = $destiny['pillars'] ?? [];
        $scores = $destiny['five_elements_scores'] ?? [];
        $fiveElementStrength = $destiny['five_element_strength'] ?? null;

        return [
            'day_stem' => mb_substr((string) ($pillars['day']['kanji'] ?? ''), 0, 1) ?: null,
            'month_branch' => mb_substr((string) ($pillars['month']['kanji'] ?? ''), 1, 1) ?: null,
            'month_pillar' => $pillars['month']['kanji'] ?? null,
            'month_ten_god' => $pillars['month']['ten_god']['name'] ?? null,
            'visible_ten_gods' => $this->visibleTenGods($pillars),
            'zokan_ten_gods' => $this->zokanTenGods($pillars),
            'five_elements_scores' => $scores,
            'five_element_strength' => $fiveElementStrength,
            'relations' => $destiny['relations'] ?? [],
            'strongest_elements' => $this->edgeElements($scores, 'desc'),
            'weakest_elements' => $this->edgeElements($scores, 'asc'),
        ];
    }

    private function visibleTenGods(array $pillars): array
    {
        $result = [];

        foreach (['year', 'month', 'day', 'hour'] as $pillarName) {
            $result[$pillarName] = $pillars[$pillarName]['ten_god']['name'] ?? null;
        }

        return $result;
    }

    private function zokanTenGods(array $pillars): array
    {
        $result = [];

        foreach (['year', 'month', 'day', 'hour'] as $pillarName) {
            $result[$pillarName] = $pillars[$pillarName]['zokan']['ten_god_name'] ?? null;
        }

        return $result;
    }

    private function edgeElements(array $scores, string $direction): array
    {
        if ($scores === []) {
            return [];
        }

        usort($scores, fn ($a, $b) => $direction === 'asc'
            ? ((float) ($a['score'] ?? 0) <=> (float) ($b['score'] ?? 0))
            : ((float) ($b['score'] ?? 0) <=> (float) ($a['score'] ?? 0)));

        $edgeScore = (float) ($scores[0]['score'] ?? 0);

        return array_values(array_filter($scores, fn ($score) => (float) ($score['score'] ?? 0) === $edgeScore));
    }
}
