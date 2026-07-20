<?php

namespace Database\Seeders;

use App\Support\SolarTermCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SolarTermDefinitionSeeder extends Seeder
{
    public function run(): void
    {
        $branchIds = DB::table('master_branches')->pluck('id', 'name');
        $now = now();

        foreach (SolarTermCatalog::TERMS as $index => $term) {
            $branchId = $term['branch'] === null ? null : ($branchIds[$term['branch']] ?? null);

            if ($term['branch'] !== null && $branchId === null) {
                throw new RuntimeException("地支マスターが不足しています: {$term['branch']}");
            }

            DB::table('solar_term_definitions')->updateOrInsert(
                ['name' => $term['name']],
                [
                    'longitude_degree' => $term['longitude_degree'],
                    'term_type' => $term['term_type'],
                    'month_branch_id' => $branchId,
                    'display_order' => $index + 1,
                    'is_month_boundary' => $term['is_month_boundary'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }
    }
}
