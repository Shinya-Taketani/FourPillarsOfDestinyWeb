<?php

declare(strict_types=1);

namespace App\Data\Appraisal;

use JsonSerializable;

final readonly class CompatibilityResultData implements JsonSerializable
{
    /**
     * @param  array<string,mixed>  $person1Result
     * @param  array<string,mixed>  $person2Result
     * @param  array<string,mixed>  $compatibility
     */
    public function __construct(
        public int $schemaVersion,
        public array $person1Result,
        public array $person2Result,
        public array $compatibility,
    ) {}

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'schema_version' => $this->schemaVersion,
            'status' => 'calculated',
            'person1_result' => $this->person1Result,
            'person2_result' => $this->person2Result,
            // TODO: Vue の person1/person2 移行後に互換キーを削除する。
            'person1' => $this->person1Result,
            'person2' => $this->person2Result,
            'compatibility' => $this->compatibility,
            'relations' => [
                'status' => 'pending',
                'items' => [],
                'source_rank' => 'PENDING',
                'source_note' => '二命式間の中立的な関係検出は未接続。既存相性評価の成立根拠は要確認。',
            ],
            'interpretation' => [
                'status' => 'pending',
                'summary' => $this->compatibility['summary'] ?? '',
                'sections' => $this->compatibility['details'] ?? [],
                'source_rank' => 'PENDING',
                'source_note' => '相性解釈の出典と泰山流としての採用可否は要確認。',
            ],
            'warnings' => $this->warnings(),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /** @return array<int,array{code:string,message:string,severity:string}> */
    private function warnings(): array
    {
        $warnings = [
            ...($this->person1Result['warnings'] ?? []),
            ...($this->person2Result['warnings'] ?? []),
        ];
        $unique = [];

        foreach ($warnings as $warning) {
            $unique[$warning['code'] ?? md5(serialize($warning))] = $warning;
        }

        return array_values($unique);
    }
}
