<?php

declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;

readonly class RelationJudgementService
{
    private const PILLAR_NAMES = ['year', 'month', 'day', 'hour'];

    /**
     * 命式内の成立関係だけを検出する。吉凶や優先順位は判定しない。
     */
    public function judgeNatalRelations(array $chart): array
    {
        [$stems, $branches] = $this->natalSubjects($chart);
        $relations = [];

        foreach ($this->pairs($stems) as [$first, $second]) {
            array_push($relations, ...$this->judgeStemPair($first, $second));
        }

        foreach ($this->pairs($branches) as [$first, $second]) {
            array_push($relations, ...$this->judgeBranchPair($first, $second));
        }

        foreach ($this->triples($branches) as [$first, $second, $third]) {
            array_push($relations, ...$this->judgeBranchTriple($first, $second, $third));
        }

        return $relations;
    }

    /**
     * 将来の大運接続用。現時点では命式とのペア関係と3支関係だけを検出する。
     */
    public function judgeNatalWithDayun(array $chart, array $dayun): array
    {
        return $this->judgeNatalAgainstPillar($chart, $dayun, 'dayun', 'natal_vs_dayun');
    }

    /**
     * 将来の流年接続用。現時点では命式とのペア関係と3支関係だけを検出する。
     */
    public function judgeNatalWithRyunen(array $chart, array $ryunen): array
    {
        return $this->judgeNatalAgainstPillar($chart, $ryunen, 'ryunen', 'natal_vs_ryunen');
    }

    public function judgeStemPair(array $first, array $second, string $targetScope = 'natal'): array
    {
        $definition = $this->findDefinition(
            config('taizan_relations.stem_combinations', []),
            [$first['name'] ?? null, $second['name'] ?? null],
        );

        if ($definition === null) {
            return [];
        }

        return [$this->result(
            'stem_combination',
            $targetScope,
            [$first, $second],
            [
                'transformed_element' => $definition['transformed_element'] ?? null,
                'transformation_status' => 'not_judged',
            ],
        )];
    }

    public function judgeBranchPair(array $first, array $second, string $targetScope = 'natal'): array
    {
        $names = [$first['name'] ?? null, $second['name'] ?? null];
        $relations = [];
        $pairDefinitions = [
            'branch_combination' => config('taizan_relations.branch_combinations', []),
            'branch_clash' => config('taizan_relations.branch_clashes', []),
            'branch_harm' => config('taizan_relations.branch_harms', []),
            'branch_break' => config('taizan_relations.branch_breaks', []),
            'branch_punishment' => config('taizan_relations.branch_punishments.pairs', []),
        ];

        foreach ($pairDefinitions as $relationType => $definitions) {
            $definition = $this->findDefinition($definitions, $names);
            if ($definition !== null) {
                $attributes = isset($definition['variant'])
                    ? ['relation_variant' => $definition['variant']]
                    : [];
                $relations[] = $this->result($relationType, $targetScope, [$first, $second], $attributes);
            }
        }

        if (($names[0] ?? null) === ($names[1] ?? null)
            && in_array($names[0], config('taizan_relations.branch_punishments.self', []), true)) {
            $relations[] = $this->result(
                'branch_punishment',
                $targetScope,
                [$first, $second],
                ['relation_variant' => 'self_punishment'],
            );
        }

        return $relations;
    }

    public function judgeBranchTriple(
        array $first,
        array $second,
        array $third,
        string $targetScope = 'natal',
    ): array {
        $names = [$first['name'] ?? null, $second['name'] ?? null, $third['name'] ?? null];
        $subjects = [$first, $second, $third];
        $relations = [];

        $punishment = $this->findDefinition(
            config('taizan_relations.branch_punishments.triples', []),
            $names,
        );
        if ($punishment !== null) {
            $relations[] = $this->result(
                'branch_punishment',
                $targetScope,
                $subjects,
                ['relation_variant' => $punishment['variant'] ?? 'three_punishment'],
            );
        }

        $threeHarmony = $this->findDefinition(config('taizan_relations.three_harmony', []), $names);
        if ($threeHarmony !== null) {
            $relations[] = $this->result(
                'three_harmony',
                $targetScope,
                $subjects,
                ['element' => $threeHarmony['element'] ?? null],
            );
        }

        $directional = $this->findDefinition(config('taizan_relations.directional_combinations', []), $names);
        if ($directional !== null) {
            $relations[] = $this->result(
                'directional_combination',
                $targetScope,
                $subjects,
                ['element' => $directional['element'] ?? null],
            );
        }

        return $relations;
    }

    private function judgeNatalAgainstPillar(
        array $chart,
        array $targetPillar,
        string $targetName,
        string $targetScope,
    ): array {
        [$natalStems, $natalBranches] = $this->natalSubjects($chart);
        [$targetStem, $targetBranch] = $this->pillarSubjects($targetPillar, $targetName);
        $relations = [];

        foreach ($natalStems as $stem) {
            array_push($relations, ...$this->judgeStemPair($stem, $targetStem, $targetScope));
        }

        foreach ($natalBranches as $branch) {
            array_push($relations, ...$this->judgeBranchPair($branch, $targetBranch, $targetScope));
        }

        foreach ($this->pairs($natalBranches) as [$first, $second]) {
            array_push(
                $relations,
                ...$this->judgeBranchTriple($first, $second, $targetBranch, $targetScope),
            );
        }

        return $relations;
    }

    private function natalSubjects(array $chart): array
    {
        $pillars = isset($chart['pillars']) && is_array($chart['pillars'])
            ? $chart['pillars']
            : $chart;
        $stems = [];
        $branches = [];

        foreach (self::PILLAR_NAMES as $pillarName) {
            $pillar = $pillars[$pillarName] ?? null;
            if (! is_array($pillar)) {
                throw new InvalidArgumentException("Pillar data is missing for {$pillarName}.");
            }

            [$stem, $branch] = $this->pillarSubjects($pillar, $pillarName);
            $stems[] = $stem;
            $branches[] = $branch;
        }

        return [$stems, $branches];
    }

    private function pillarSubjects(array $pillar, string $pillarName): array
    {
        $pillar = isset($pillar['pillar']) && is_array($pillar['pillar'])
            ? $pillar['pillar']
            : $pillar;
        $kanji = (string) ($pillar['kanji'] ?? '');
        $stemName = (string) ($pillar['stem'] ?? $pillar['stem_name'] ?? mb_substr($kanji, 0, 1));
        $branchName = (string) ($pillar['branch'] ?? $pillar['branch_name'] ?? mb_substr($kanji, 1, 1));

        if ($stemName === '' || $branchName === '') {
            throw new InvalidArgumentException("Stem or branch name is missing for {$pillarName}.");
        }

        return [
            ['pillar' => $pillarName, 'type' => 'stem', 'name' => $stemName],
            ['pillar' => $pillarName, 'type' => 'branch', 'name' => $branchName],
        ];
    }

    private function result(
        string $relationType,
        string $targetScope,
        array $subjects,
        array $attributes = [],
    ): array {
        $metadata = config('taizan_relations.metadata', []);

        return [
            'relation_type' => $relationType,
            'target_scope' => $targetScope,
            'subjects' => array_values($subjects),
            'is_established' => true,
            ...$attributes,
            'interpretation_status' => 'pending',
            'source_rank' => $metadata['source_rank'] ?? 'PENDING',
            'source_note' => $metadata['source_note'] ?? '泰山流における成立条件・優先順位・吉凶判断は要確認。',
        ];
    }

    private function findDefinition(array $definitions, array $names): ?array
    {
        if (in_array(null, $names, true) || in_array('', $names, true)) {
            return null;
        }

        $sortedNames = $names;
        sort($sortedNames);

        foreach ($definitions as $definition) {
            $members = $definition['members'] ?? [];
            sort($members);

            if ($members === $sortedNames) {
                return $definition;
            }
        }

        return null;
    }

    private function pairs(array $subjects): array
    {
        $pairs = [];

        for ($first = 0; $first < count($subjects) - 1; $first++) {
            for ($second = $first + 1; $second < count($subjects); $second++) {
                $pairs[] = [$subjects[$first], $subjects[$second]];
            }
        }

        return $pairs;
    }

    private function triples(array $subjects): array
    {
        $triples = [];

        for ($first = 0; $first < count($subjects) - 2; $first++) {
            for ($second = $first + 1; $second < count($subjects) - 1; $second++) {
                for ($third = $second + 1; $third < count($subjects); $third++) {
                    $triples[] = [$subjects[$first], $subjects[$second], $subjects[$third]];
                }
            }
        }

        return $triples;
    }
}
