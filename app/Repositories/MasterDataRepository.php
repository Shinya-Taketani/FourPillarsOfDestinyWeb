<?php

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MasterDataRepository
{
    private ?Collection $elementsById = null;

    private ?Collection $elementsByName = null;

    private ?Collection $stemsById = null;

    private ?Collection $stemsByName = null;

    private ?Collection $branchesById = null;

    private ?Collection $branchesByName = null;

    private ?Collection $tenGodsById = null;

    private ?Collection $tenGodsByName = null;

    private ?Collection $twelveLifeStagesById = null;

    private ?Collection $twelveLifeStagesByName = null;

    private ?Collection $zokanRatiosByBranchId = null;

    private ?Collection $seasonalMultipliersBySeasonId = null;

    public function elementsById(): Collection
    {
        return $this->elementsById ??= DB::table('master_elements')
            ->orderBy('id')
            ->get()
            ->keyBy('id');
    }

    public function elementsByName(): Collection
    {
        return $this->elementsByName ??= $this->elementsById()->keyBy('name');
    }

    public function stemsById(): Collection
    {
        return $this->stemsById ??= DB::table('master_stems')
            ->orderBy('id')
            ->get()
            ->keyBy('id');
    }

    public function stemsByName(): Collection
    {
        return $this->stemsByName ??= $this->stemsById()->keyBy('name');
    }

    public function branchesById(): Collection
    {
        return $this->branchesById ??= DB::table('master_branches')
            ->orderBy('id')
            ->get()
            ->keyBy('id');
    }

    public function branchesByName(): Collection
    {
        return $this->branchesByName ??= $this->branchesById()->keyBy('name');
    }

    public function tenGodsById(): Collection
    {
        return $this->tenGodsById ??= DB::table('master_ten_gods')
            ->orderBy('id')
            ->get()
            ->keyBy('id');
    }

    public function tenGodsByName(): Collection
    {
        return $this->tenGodsByName ??= $this->tenGodsById()->keyBy('name');
    }

    public function twelveLifeStagesById(): Collection
    {
        return $this->twelveLifeStagesById ??= DB::table('master_twelve_life_stages')
            ->orderBy('id')
            ->get()
            ->keyBy('id');
    }

    public function twelveLifeStagesByName(): Collection
    {
        return $this->twelveLifeStagesByName ??= $this->twelveLifeStagesById()->keyBy('name');
    }

    public function zokanRatiosByBranchId(): Collection
    {
        return $this->zokanRatiosByBranchId ??= DB::table('master_zokan_ratios')
            ->orderBy('branch_id')
            ->orderBy('id')
            ->get()
            ->groupBy('branch_id');
    }

    public function seasonalMultipliersBySeasonId(): Collection
    {
        return $this->seasonalMultipliersBySeasonId ??= DB::table('master_seasonal_multipliers')
            ->orderBy('season_id')
            ->orderBy('element_id')
            ->get()
            ->groupBy('season_id');
    }

    public function seasonalMultipliersByMonthBranchId(int $monthBranchId): Collection
    {
        $branch = $this->getBranchById($monthBranchId);

        if ($branch === null) {
            return collect();
        }

        return $this->seasonalMultipliersBySeasonId()->get((int) $branch->season_id, collect());
    }

    public function getElementById(int $id): ?object
    {
        return $this->elementsById()->get($id);
    }

    public function getStemById(int $id): ?object
    {
        return $this->stemsById()->get($id);
    }

    public function getStemByName(string $name): ?object
    {
        return $this->stemsByName()->get($name);
    }

    public function getBranchById(int $id): ?object
    {
        return $this->branchesById()->get($id);
    }

    public function getBranchByName(string $name): ?object
    {
        return $this->branchesByName()->get($name);
    }
}
