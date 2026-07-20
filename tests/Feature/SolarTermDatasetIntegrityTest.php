<?php

namespace Tests\Feature;

use App\Services\SolarTermCsvReader;
use App\Support\SolarTermCatalog;
use Database\Seeders\SolarTermDefinitionSeeder;
use Database\Seeders\SolarTermEventSeeder;
use Database\Seeders\TaizanMasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class SolarTermDatasetIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_naoj_dataset_is_structurally_complete_and_preserves_audit_decisions(): void
    {
        $this->seed(TaizanMasterSeeder::class);
        $this->seed(SolarTermDefinitionSeeder::class);
        $this->seed(SolarTermEventSeeder::class);

        $this->assertSame(24, DB::table('solar_term_definitions')->count());
        $this->assertSame(12, DB::table('solar_term_definitions')->where('is_month_boundary', true)->count());
        $this->assertSame(12, DB::table('solar_term_definitions')->where('term_type', 'middle_term')->count());
        $this->assertSame(4872, DB::table('solar_term_events')->count());
        $this->assertSame(4824, DB::table('solar_term_events')->where('adopted', true)->where('source_rank', 'S2')->count());
        $this->assertSame(48, DB::table('solar_term_events')->where('verification_status', 'rejected')->where('adopted', false)->count());
        $this->assertSame(24, DB::table('solar_term_events')->where('verification_status', 'verified')->where('adopted', true)->count());
        $this->assertSame(4872, DB::table('solar_term_events')->where('timezone', 'Asia/Tokyo')->where('calendar_system', 'modern_astronomical')->count());
        $this->assertSame(4872, DB::table('solar_term_events')->where('precision_level', 'minute')->count());
        $this->assertSame(4872, DB::table('solar_term_events')->whereNotNull('source_accessed_on')->whereNotNull('raw_content_hash')->count());

        $termTypeCounts = DB::table('solar_term_events')
            ->join('solar_term_definitions', 'solar_term_definitions.id', '=', 'solar_term_events.solar_term_definition_id')
            ->selectRaw('solar_term_definitions.term_type, COUNT(*) AS aggregate')
            ->groupBy('solar_term_definitions.term_type')
            ->pluck('aggregate', 'term_type');
        $this->assertSame(2436, (int) $termTypeCounts['major_term']);
        $this->assertSame(2436, (int) $termTypeCounts['middle_term']);

        $yearCounts = DB::table('solar_term_events')->selectRaw('year, COUNT(*) AS aggregate')->groupBy('year')->orderBy('year')->pluck('aggregate', 'year');
        $this->assertCount(203, $yearCounts);
        foreach ($yearCounts as $count) {
            $this->assertSame(24, (int) $count);
        }

        $duplicates = DB::table('solar_term_events')
            ->select('year', 'solar_term_definition_id')
            ->groupBy('year', 'solar_term_definition_id')
            ->havingRaw('COUNT(*) > 1')
            ->count();
        $this->assertSame(0, $duplicates);

        foreach (SolarTermCatalog::TERMS as $term) {
            $this->assertDatabaseHas('solar_term_definitions', [
                'name' => $term['name'],
                'longitude_degree' => $term['longitude_degree'],
                'term_type' => $term['term_type'],
                'is_month_boundary' => $term['is_month_boundary'],
            ]);
        }

        $expected2026 = [
            '小寒' => '2026-01-05 17:23:00', '立春' => '2026-02-04 05:02:00',
            '啓蟄' => '2026-03-05 22:59:00', '清明' => '2026-04-05 03:40:00',
            '立夏' => '2026-05-05 20:49:00', '芒種' => '2026-06-06 00:48:00',
            '小暑' => '2026-07-07 10:57:00', '立秋' => '2026-08-07 20:43:00',
            '白露' => '2026-09-07 23:41:00', '寒露' => '2026-10-08 15:29:00',
            '立冬' => '2026-11-07 18:52:00', '大雪' => '2026-12-07 11:53:00',
        ];

        foreach ($expected2026 as $name => $startedAt) {
            $this->assertDatabaseHas('solar_term_events', [
                'year' => 2026,
                'solar_term_definition_id' => DB::table('solar_term_definitions')->where('name', $name)->value('id'),
                'started_at' => $startedAt,
                'adopted' => true,
                'source_rank' => 'S2',
                'verification_status' => 'verified',
            ]);
        }

        foreach ([
            1971 => ['actual' => '1971-02-04 20:25:00', 'expected' => '1971-02-04 20:26:00'],
            1980 => ['actual' => '1980-02-05 01:09:00', 'expected' => '1980-02-05 01:10:00'],
        ] as $year => $audit) {
            $event = DB::table('solar_term_events')
                ->where('year', $year)
                ->where('solar_term_definition_id', DB::table('solar_term_definitions')->where('name', '立春')->value('id'))
                ->first();

            $this->assertNotNull($event);
            $this->assertSame($audit['actual'], $event->started_at);
            $this->assertFalse((bool) $event->adopted);
            $this->assertSame('rejected', $event->verification_status);
            $this->assertStringContainsString($audit['expected'], (string) $event->note);
        }
    }

    public function test_csv_checksum_is_valid_and_incomplete_csv_is_rejected(): void
    {
        $csvPath = database_path('data/solar_terms/naoj_1899_2101.csv');
        $checksum = preg_split('/\s+/', trim((string) file_get_contents($csvPath.'.sha256')))[0] ?? '';
        $this->assertSame($checksum, hash_file('sha256', $csvPath));

        $temporaryPath = tempnam(sys_get_temp_dir(), 'solar-terms-incomplete-');
        $this->assertNotFalse($temporaryPath);
        $lines = file($csvPath);
        $this->assertNotFalse($lines);
        file_put_contents($temporaryPath, implode('', array_slice($lines, 0, 24)));

        try {
            app(SolarTermCsvReader::class)->read($temporaryPath, 1899, 1899);
            $this->fail('不完全CSVは拒否される必要があります。');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('不完全', $exception->getMessage());
        } finally {
            @unlink($temporaryPath);
        }
    }

    public function test_csv_rejects_unknown_term_and_wrong_longitude(): void
    {
        $csvPath = database_path('data/solar_terms/naoj_1899_2101.csv');
        $lines = file($csvPath);
        $this->assertNotFalse($lines);

        foreach ([
            '節気名' => static function (array $row): array {
                $row[1] = '不明節気';

                return $row;
            },
            '黄経' => static function (array $row): array {
                $row[2] = '999';

                return $row;
            },
        ] as $expectedMessage => $mutate) {
            $temporaryPath = tempnam(sys_get_temp_dir(), 'solar-terms-invalid-');
            $this->assertNotFalse($temporaryPath);
            $row = str_getcsv($lines[1], escape: '');
            $row = $mutate($row);
            $invalidLines = $lines;
            $handle = fopen($temporaryPath, 'wb');
            $this->assertNotFalse($handle);

            try {
                fwrite($handle, $invalidLines[0]);
                fputcsv($handle, $row, ',', '"', '');
                foreach (array_slice($invalidLines, 2) as $line) {
                    fwrite($handle, $line);
                }
            } finally {
                fclose($handle);
            }

            try {
                app(SolarTermCsvReader::class)->read($temporaryPath, 1899, 2101);
                $this->fail('不正CSVは拒否される必要があります。');
            } catch (RuntimeException $exception) {
                $this->assertStringContainsString($expectedMessage, $exception->getMessage());
            } finally {
                @unlink($temporaryPath);
            }
        }
    }

    public function test_event_seeder_replaces_legacy_s_rows_and_remains_idempotent(): void
    {
        $this->seed(TaizanMasterSeeder::class);
        $this->seed(SolarTermDefinitionSeeder::class);
        $this->seed(SolarTermEventSeeder::class);
        DB::table('solar_term_events')->update(['source_rank' => 'S']);

        $this->seed(SolarTermEventSeeder::class);
        $this->seed(SolarTermEventSeeder::class);

        $this->assertSame(4872, DB::table('solar_term_events')->count());
        $this->assertSame(4872, DB::table('solar_term_events')->where('source_rank', 'S2')->count());
        $this->assertSame(0, DB::table('solar_term_events')->where('source_rank', 'S')->count());
    }
}
