<?php

declare(strict_types=1);

namespace App\Data\Appraisal;

use JsonSerializable;

final readonly class AppraisalResultData implements JsonSerializable
{
    /**
     * @param  array<string,mixed>  $input
     * @param  array<string,mixed>  $calculationMetadata
     * @param  array<string,PillarData>  $chart
     * @param  array<string,array<int,array<string,mixed>>>  $hiddenStems
     * @param  array<string,array<string,mixed>>  $tenGods
     * @param  array<string,array<string,mixed>|null>  $twelveLifeStages
     * @param  array<string,mixed>  $fiveElementStrength
     * @param  array<string,mixed>  $relations
     * @param  array<string,mixed>  $dayun
     * @param  array<string,mixed>  $ryunen
     * @param  array<string,mixed>  $judgement
     * @param  array<string,mixed>  $interpretation
     * @param  array<int,array{code:string,message:string,severity:string}>  $warnings
     * @param  array<string,mixed>  $legacyResult
     */
    public function __construct(
        public int $schemaVersion,
        public string $status,
        public array $input,
        public array $calculationMetadata,
        public array $chart,
        public array $hiddenStems,
        public array $tenGods,
        public array $twelveLifeStages,
        public array $fiveElementStrength,
        public array $relations,
        public array $dayun,
        public array $ryunen,
        public array $judgement,
        public array $interpretation,
        public array $warnings,
        private array $legacyResult,
    ) {}

    /**
     * 既存 Service の配列を壊さず、公開用の安定キーへ整形する。
     *
     * @param  array<string,mixed>  $legacyResult
     * @param  array<string,mixed>  $input
     * @param  array<string,mixed>  $calculationMetadata
     * @param  array<int,array{code:string,message:string,severity:string}>  $warnings
     */
    public static function fromCalculation(
        array $legacyResult,
        array $input,
        array $calculationMetadata,
        array $warnings,
    ): self {
        $pillars = $legacyResult['pillars'] ?? [];
        $chart = [];
        $hiddenStems = [];
        $tenGods = [];
        $twelveLifeStages = [];

        foreach (['year', 'month', 'day', 'hour'] as $pillarName) {
            $pillar = $pillars[$pillarName];
            $chart[$pillarName] = PillarData::fromArray($pillar);
            $hiddenStems[$pillarName] = isset($pillar['zokan']['stem_id'])
                ? [[
                    'stem_id' => $pillar['zokan']['stem_id'],
                    'stem_name' => $pillar['zokan']['name'] ?? null,
                    'ten_god_name' => $pillar['zokan']['ten_god_name'] ?? null,
                ]]
                : [];
            $tenGods[$pillarName] = [
                'visible' => $pillar['ten_god'] ?? null,
                'hidden_stem' => isset($pillar['zokan'])
                    ? ['name' => $pillar['zokan']['ten_god_name'] ?? null]
                    : null,
            ];
            $twelveLifeStages[$pillarName] = $pillar['twelve_life_stage'] ?? null;
        }

        $judgement = $legacyResult['judgement'] ?? [];
        $judgementRelations = $judgement['relations'] ?? [];
        $relations = [
            'status' => $judgementRelations['status'] ?? 'pending',
            'items' => $judgementRelations['items'] ?? ($legacyResult['relations'] ?? []),
            'source_rank' => $judgementRelations['source_rank'] ?? 'PENDING',
            'source_note' => $judgementRelations['source_note'] ?? '関係成立後の泰山流固有判断は要確認。',
        ];
        $interpretation = [
            'status' => 'pending',
            'summary' => '',
            'sections' => $legacyResult['appraisal'] ?? [],
            'source_rank' => 'PENDING',
            'source_note' => '解釈文の出典と泰山流としての採用可否は要確認。',
        ];

        return new self(
            schemaVersion: 1,
            status: 'calculated',
            input: $input,
            calculationMetadata: $calculationMetadata,
            chart: $chart,
            hiddenStems: $hiddenStems,
            tenGods: $tenGods,
            twelveLifeStages: $twelveLifeStages,
            fiveElementStrength: $legacyResult['five_element_strength'] ?? [],
            relations: $relations,
            dayun: $legacyResult['dayun'] ?? [],
            ryunen: $legacyResult['ryunen'] ?? [],
            judgement: $judgement,
            interpretation: $interpretation,
            warnings: $warnings,
            legacyResult: $legacyResult,
        );
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        $legacy = $this->legacyResult;
        // TODO: 旧 relations 配列の参照元移行後に relation_items を削除する。
        $legacy['relation_items'] = $legacy['relations'] ?? [];

        return [
            ...$legacy,
            'schema_version' => $this->schemaVersion,
            'status' => $this->status,
            'input' => $this->input,
            'calculation_metadata' => $this->calculationMetadata,
            'chart' => array_map(
                static fn (PillarData $pillar): array => $pillar->toArray(),
                $this->chart,
            ),
            'hidden_stems' => $this->hiddenStems,
            'ten_gods' => $this->tenGods,
            'twelve_life_stages' => $this->twelveLifeStages,
            'five_element_strength' => $this->fiveElementStrength,
            'relations' => $this->relations,
            'dayun' => $this->dayun,
            'ryunen' => $this->ryunen,
            'judgement' => $this->judgement,
            'interpretation' => $this->interpretation,
            'warnings' => $this->warnings,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
