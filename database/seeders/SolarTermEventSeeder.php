<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Services\AnnualSolarTermCsvReader;
use App\Services\SolarTermCsvReader;
use App\Support\NaojSolarTermAuditCatalog;
use App\Support\SolarTermSourcePriority;
use DateTimeImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SolarTermEventSeeder extends Seeder
{
    private const FROM_YEAR = 1899;

    private const TO_YEAR = 2101;

    private const CSV_PATH = 'data/solar_terms/naoj_1899_2101.csv';

    private const ANNUAL_CSV_PATH = 'data/solar_terms/naoj_annual_official_events.csv';

    public function run(SolarTermCsvReader $reader, AnnualSolarTermCsvReader $annualReader): void
    {
        $csvPath = database_path(self::CSV_PATH);
        $longTermRows = $reader->read($csvPath, self::FROM_YEAR, self::TO_YEAR, $csvPath.'.sha256');
        $annualCsvPath = database_path(self::ANNUAL_CSV_PATH);
        $annualRows = $annualReader->read($annualCsvPath, $annualCsvPath.'.sha256');
        $annualByEvent = [];

        foreach ($annualRows as $row) {
            $annualByEvent[$this->eventKey($row)] = $row;
        }

        $rows = [];

        foreach ($longTermRows as $row) {
            $annualRow = $annualByEvent[$this->eventKey($row)] ?? null;
            $rows[] = $annualRow === null
                ? [...$row, 'adopted' => true, 'verification_status' => 'imported']
                : $this->supersedeLongTermRow($row, $annualRow);
        }

        foreach ($annualRows as $row) {
            $rows[] = [...$row, 'raw_content_hash' => null];
        }

        $rows = $this->applySourcePriority($rows);
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
                'precision_level' => $row['precision_level'],
                'source_accessed_on' => $row['source_accessed_on'],
                'source_citation_text' => $row['source_citation_text'],
                'raw_content_hash' => $row['raw_content_hash'],
                'verification_status' => $row['verification_status'],
                'note' => $row['note'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::transaction(function () use ($inserts): void {
            DB::table('solar_term_events')
                ->where('adopted', true)
                ->update(['adopted' => false, 'updated_at' => now()]);

            DB::table('solar_term_events')
                ->where('source_rank', 'S')
                ->delete();

            foreach (array_chunk($inserts, 500) as $chunk) {
                DB::table('solar_term_events')->upsert(
                    $chunk,
                    ['year', 'solar_term_definition_id', 'source_rank'],
                    [
                        'started_at', 'timezone', 'calendar_system', 'source_title', 'source_url',
                        'adopted', 'precision_level', 'source_accessed_on', 'source_citation_text',
                        'raw_content_hash', 'verification_status', 'note', 'updated_at',
                    ],
                );
            }
        });
    }

    /** @param array{year:int,term_name:string} $row */
    private function eventKey(array $row): string
    {
        return $row['year'].'|'.$row['term_name'];
    }

    /**
     * @param  array<string,mixed>  $longTermRow
     * @param  array<string,mixed>  $annualRow
     * @return array<string,mixed>
     */
    private function supersedeLongTermRow(array $longTermRow, array $annualRow): array
    {
        $status = NaojSolarTermAuditCatalog::compare(
            (string) $annualRow['started_at'],
            (string) $longTermRow['started_at'],
        );
        $note = (string) $longTermRow['note'];

        if ($status === NaojSolarTermAuditCatalog::SUPERSEDED_MATCHED) {
            $note .= ' 年次暦要項S1と一致するため、正式計算ではS1を優先。長期版値は監査用として保持。';
        } else {
            $annual = new DateTimeImmutable((string) $annualRow['started_at']);
            $longTerm = new DateTimeImmutable((string) $longTermRow['started_at']);
            $differenceInMinutes = (int) (abs($annual->getTimestamp() - $longTerm->getTimestamp()) / 60);
            $note .= sprintf(
                ' 年次暦要項S1と%d分差があるため、正式計算ではS1を優先。長期版値は監査用として保持。S2=%s / S1=%s。',
                $differenceInMinutes,
                $longTermRow['started_at'],
                $annualRow['started_at'],
            );
        }

        return [
            ...$longTermRow,
            'adopted' => false,
            'verification_status' => $status,
            'note' => $note,
        ];
    }

    /**
     * @param  list<array<string,mixed>>  $rows
     * @return list<array<string,mixed>>
     */
    private function applySourcePriority(array $rows): array
    {
        $highestPriorityByEvent = [];

        foreach ($rows as $row) {
            $priority = SolarTermSourcePriority::priority((string) $row['source_rank']);

            if ($priority === null) {
                throw new RuntimeException("未知のsource_rankは正式採用できません: {$row['source_rank']}");
            }

            $key = $this->eventKey($row);
            $highestPriorityByEvent[$key] = max($highestPriorityByEvent[$key] ?? 0, $priority);
        }

        return array_map(function (array $row) use ($highestPriorityByEvent): array {
            $priority = SolarTermSourcePriority::priority((string) $row['source_rank']);
            $row['adopted'] = $priority === $highestPriorityByEvent[$this->eventKey($row)];

            return $row;
        }, $rows);
    }
}
