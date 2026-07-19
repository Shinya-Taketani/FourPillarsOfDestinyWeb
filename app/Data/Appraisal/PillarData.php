<?php

declare(strict_types=1);

namespace App\Data\Appraisal;

use JsonSerializable;

final readonly class PillarData implements JsonSerializable
{
    public function __construct(
        public int $stemId,
        public int $branchId,
        public string $stemName,
        public string $branchName,
    ) {}

    /**
     * @param  array{stem_id:int,branch_id:int,stem_name:string,branch_name:string}  $pillar
     */
    public static function fromArray(array $pillar): self
    {
        return new self(
            stemId: $pillar['stem_id'],
            branchId: $pillar['branch_id'],
            stemName: $pillar['stem_name'],
            branchName: $pillar['branch_name'],
        );
    }

    public function pillar(): string
    {
        return $this->stemName.$this->branchName;
    }

    /** @return array{stem_id:int,branch_id:int,stem_name:string,branch_name:string,pillar:string} */
    public function toArray(): array
    {
        return [
            'stem_id' => $this->stemId,
            'branch_id' => $this->branchId,
            'stem_name' => $this->stemName,
            'branch_name' => $this->branchName,
            'pillar' => $this->pillar(),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
