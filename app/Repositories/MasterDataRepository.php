<?php

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use stdClass;

class MasterDataRepository
{
    /** @var Collection<int|string,stdClass>|null */
    private ?Collection $elementsById = null;

    /** @var Collection<string,stdClass>|null */
    private ?Collection $elementsByName = null;

    /** @var Collection<int|string,stdClass>|null */
    private ?Collection $stemsById = null;

    /** @var Collection<string,stdClass>|null */
    private ?Collection $stemsByName = null;

    /** @var Collection<int|string,stdClass>|null */
    private ?Collection $branchesById = null;

    /** @var Collection<string,stdClass>|null */
    private ?Collection $branchesByName = null;

    /** @var Collection<int|string,stdClass>|null */
    private ?Collection $tenGodsById = null;

    /** @var Collection<string,stdClass>|null */
    private ?Collection $tenGodsByName = null;

    /** @var Collection<int|string,stdClass>|null */
    private ?Collection $twelveLifeStagesById = null;

    /** @var Collection<string,stdClass>|null */
    private ?Collection $twelveLifeStagesByName = null;

    /** @var Collection<int|string,Collection<int,stdClass>>|null */
    private ?Collection $zokanRatiosByBranchId = null;

    /** @var Collection<int|string,Collection<int,stdClass>>|null */
    private ?Collection $seasonalMultipliersBySeasonId = null;

    /** @return Collection<int|string,stdClass> */
    public function elementsById(): Collection
    {
        return $this->elementsById ??= DB::table('master_elements')
            ->orderBy('id')
            ->get()
            ->keyBy('id');
    }

    /** @return Collection<string,stdClass> */
    public function elementsByName(): Collection
    {
        return $this->elementsByName ??= $this->elementsById()->keyBy('name');
    }

    /** @return Collection<int|string,stdClass> */
    public function stemsById(): Collection
    {
        return $this->stemsById ??= DB::table('master_stems')
            ->orderBy('id')
            ->get()
            ->keyBy('id');
    }

    /** @return Collection<string,stdClass> */
    public function stemsByName(): Collection
    {
        return $this->stemsByName ??= $this->stemsById()->keyBy('name');
    }

    /** @return Collection<int|string,stdClass> */
    public function branchesById(): Collection
    {
        return $this->branchesById ??= DB::table('master_branches')
            ->orderBy('id')
            ->get()
            ->keyBy('id');
    }

    /** @return Collection<string,stdClass> */
    public function branchesByName(): Collection
    {
        return $this->branchesByName ??= $this->branchesById()->keyBy('name');
    }

    /** @return Collection<int|string,stdClass> */
    public function tenGodsById(): Collection
    {
        return $this->tenGodsById ??= DB::table('master_ten_gods')
            ->orderBy('id')
            ->get()
            ->keyBy('id');
    }

    /** @return Collection<string,stdClass> */
    public function tenGodsByName(): Collection
    {
        return $this->tenGodsByName ??= $this->tenGodsById()->keyBy('name');
    }

    /** @return Collection<int|string,stdClass> */
    public function twelveLifeStagesById(): Collection
    {
        return $this->twelveLifeStagesById ??= DB::table('master_twelve_life_stages')
            ->orderBy('id')
            ->get()
            ->keyBy('id');
    }

    /** @return Collection<string,stdClass> */
    public function twelveLifeStagesByName(): Collection
    {
        return $this->twelveLifeStagesByName ??= $this->twelveLifeStagesById()->keyBy('name');
    }

    /** @return Collection<int|string,Collection<int,stdClass>> */
    public function zokanRatiosByBranchId(): Collection
    {
        return $this->zokanRatiosByBranchId ??= DB::table('master_zokan_ratios')
            ->orderBy('branch_id')
            ->orderBy('id')
            ->get()
            ->groupBy('branch_id');
    }

    /** @return Collection<int|string,Collection<int,stdClass>> */
    public function seasonalMultipliersBySeasonId(): Collection
    {
        return $this->seasonalMultipliersBySeasonId ??= DB::table('master_seasonal_multipliers')
            ->orderBy('season_id')
            ->orderBy('element_id')
            ->get()
            ->groupBy('season_id');
    }

    /** @return Collection<int,stdClass> */
    public function seasonalMultipliersByMonthBranchId(int $monthBranchId): Collection
    {
        $branch = $this->getBranchById($monthBranchId);

        if ($branch === null) {
            return collect();
        }

        return $this->seasonalMultipliersBySeasonId()->get((int) $branch->season_id, collect());
    }

    public function getElementById(int $id): ?stdClass
    {
        return $this->elementsById()->get($id);
    }

    public function getStemById(int $id): ?stdClass
    {
        return $this->stemsById()->get($id);
    }

    public function getStemByName(string $name): ?stdClass
    {
        return $this->stemsByName()->get($name);
    }

    public function getBranchById(int $id): ?stdClass
    {
        return $this->branchesById()->get($id);
    }

    public function getBranchByName(string $name): ?stdClass
    {
        return $this->branchesByName()->get($name);
    }

    public function getTwelveLifeStageById(int $id): ?stdClass
    {
        return $this->twelveLifeStagesById()->get($id);
    }
}
