<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Services\SolarTermCsvReader;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SolarTermEventSeeder extends Seeder
{
    private const FROM_YEAR = 1899;

    private const TO_YEAR = 2101;

    private const CSV_PATH = 'data/solar_terms/naoj_1899_2101.csv';

    public function run(SolarTermCsvReader $reader): void
    {
        $csvPath = database_path(self::CSV_PATH);
        $rows = $reader->read($csvPath, self::FROM_YEAR, self::TO_YEAR, $csvPath.'.sha256');
        $definitionIds = DB::table('solar_term_definitions')->pluck('id', 'name');
        $now = now();
        $inserts = [];

        foreach ($rows as $row) {
            $definitionId = $definitionIds[$row['term_name']] ?? null;

            if ($definitionId === null) {
                throw new RuntimeException("節気定義が不足しています: {$row['term_name']}");
            }

            $inserts[] = [
                'year' => $row['year'],
                'solar_term_definition_id' => $definitionId,
                'started_at' => $row['started_at'],
                'timezone' => $row['timezone'],
                'calendar_system' => $row['calendar_system'],
                'source_title' => $row['source_title'],
                'source_url' => $row['source_url'],
                'source_rank' => $row['source_rank'],
                'adopted' => $row['adopted'],
                'note' => $row['note'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::transaction(function () use ($inserts): void {
            foreach (array_chunk($inserts, 500) as $chunk) {
                DB::table('solar_term_events')->upsert(
                    $chunk,
                    ['year', 'solar_term_definition_id', 'source_rank'],
                    [
                        'started_at', 'timezone', 'calendar_system', 'source_title', 'source_url',
                        'adopted', 'note', 'updated_at',
                    ],
                );
            }
        });
    }
}
