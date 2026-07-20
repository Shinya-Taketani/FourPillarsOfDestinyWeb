<?php

namespace Tests\Feature;

use App\Services\Calendar\NaojSolarTermClient;
use App\Services\SolarTermCsvReader;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FetchNaojSolarTermsCommandTest extends TestCase
{
    public function test_command_posts_the_confirmed_form_and_generates_a_valid_csv_atomically(): void
    {
        Http::fake([
            NaojSolarTermClient::BASE_URL => Http::response(
                $this->naojHtml(2026),
                200,
                ['Content-Type' => 'text/html; charset=EUC-JP'],
            ),
        ]);

        [$outputPath, $rawDirectory] = $this->paths('success');
        $this->cleanPaths($outputPath, $rawDirectory);

        try {
            $this->runCommand($outputPath, $rawDirectory)->assertSuccessful();

            $rows = app(SolarTermCsvReader::class)->read($outputPath, 2026, 2026, $outputPath.'.sha256');
            $this->assertCount(24, $rows);
            $this->assertSame('2026-02-04 05:02:00', collect($rows)->firstWhere('term_name', '立春')['started_at']);
            $this->assertSame('verified', $rows[0]['verification_status']);
            $this->assertSame('S2', $rows[0]['source_rank']);
            $this->assertFileExists($rawDirectory.'/2026.html');
            $this->assertFileExists($rawDirectory.'/2026.json');
            $this->assertSame([], glob($outputPath.'.tmp.*') ?: []);

            Http::assertSentCount(1);
            Http::assertSent(static fn (Request $request): bool => $request->url() === NaojSolarTermClient::BASE_URL
                && $request->method() === 'POST'
                && str_contains($request->body(), 'year=2026')
                && str_contains($request->body(), 'lst=9')
                && str_contains($request->body(), 'phenom=50')
                && str_contains($request->body(), 'body=0&body=1'));
        } finally {
            $this->cleanPaths($outputPath, $rawDirectory);
        }
    }

    public function test_command_reuses_verified_raw_html_without_another_http_request(): void
    {
        Http::fake([
            NaojSolarTermClient::BASE_URL => Http::response($this->naojHtml(2026), 200),
        ]);
        [$outputPath, $rawDirectory] = $this->paths('cache');
        $this->cleanPaths($outputPath, $rawDirectory);

        try {
            $this->runCommand($outputPath, $rawDirectory)->assertSuccessful();
            $this->runCommand($outputPath, $rawDirectory)->assertSuccessful();
            Http::assertSentCount(1);
        } finally {
            $this->cleanPaths($outputPath, $rawDirectory);
        }
    }

    public function test_command_retries_a_temporary_failure_and_then_succeeds(): void
    {
        Http::fakeSequence()
            ->push('temporary', 500)
            ->push($this->naojHtml(2026), 200);
        [$outputPath, $rawDirectory] = $this->paths('retry-success');
        $this->cleanPaths($outputPath, $rawDirectory);

        try {
            $this->runCommand($outputPath, $rawDirectory)->assertSuccessful();
            Http::assertSentCount(2);
        } finally {
            $this->cleanPaths($outputPath, $rawDirectory);
        }
    }

    public function test_command_stops_after_retry_limit_and_preserves_existing_csv(): void
    {
        Http::fakeSequence()
            ->push('temporary', 500)
            ->push('temporary', 500)
            ->push('temporary', 500)
            ->push('temporary', 500);
        [$outputPath, $rawDirectory] = $this->paths('retry-failure');
        $this->cleanPaths($outputPath, $rawDirectory);
        File::put($outputPath, 'existing verified data');

        try {
            $this->runCommand($outputPath, $rawDirectory)->assertFailed();
            $this->assertSame('existing verified data', File::get($outputPath));
            Http::assertSentCount(4);
        } finally {
            $this->cleanPaths($outputPath, $rawDirectory);
        }
    }

    public function test_command_rejects_connection_timeout_after_retry_limit(): void
    {
        $attempts = 0;
        Http::fake(static function () use (&$attempts): never {
            $attempts++;
            throw new ConnectionException('connection timed out');
        });
        [$outputPath, $rawDirectory] = $this->paths('timeout');
        $this->cleanPaths($outputPath, $rawDirectory);

        try {
            $this->runCommand($outputPath, $rawDirectory)->assertFailed();
            $this->assertFileDoesNotExist($outputPath);
            $this->assertSame(4, $attempts);
        } finally {
            $this->cleanPaths($outputPath, $rawDirectory);
        }
    }

    public function test_command_rejects_incomplete_or_changed_html_without_overwriting_csv(): void
    {
        $validHtml = $this->naojHtml(2026);
        $cases = [
            '23件' => $this->naojHtml(2026, complete: false),
            '重複' => str_replace('大寒(黄経300°)', '小寒(黄経285°)', $validHtml),
            '黄経' => str_replace('小寒(黄経285°)', '小寒(黄経286°)', $validHtml),
            '中央標準時' => str_replace('標準時:UT+9', '標準時:UT+8', $validHtml),
            'HTML構造' => str_replace('id="phenom"', 'id="changed"', $validHtml),
        ];

        foreach ($cases as $label => $html) {
            Http::fake([NaojSolarTermClient::BASE_URL => Http::response($html, 200)]);
            [$outputPath, $rawDirectory] = $this->paths('invalid-'.md5($label));
            $this->cleanPaths($outputPath, $rawDirectory);
            File::put($outputPath, 'existing verified data');

            try {
                $this->runCommand($outputPath, $rawDirectory)->assertFailed();
                $this->assertSame('existing verified data', File::get($outputPath), $label);
                $this->assertFileDoesNotExist($outputPath.'.sha256');
            } finally {
                $this->cleanPaths($outputPath, $rawDirectory);
            }
        }
    }

    private function runCommand(string $outputPath, string $rawDirectory): mixed
    {
        return $this->artisan('calendar:fetch-naoj-solar-terms', [
            '--from' => 2026,
            '--to' => 2026,
            '--output' => $outputPath,
            '--raw-dir' => $rawDirectory,
            '--delay' => 0,
        ]);
    }

    /** @return array{string,string} */
    private function paths(string $suffix): array
    {
        return [
            storage_path("framework/testing/naoj-command-{$suffix}.csv"),
            storage_path("framework/testing/naoj-command-raw-{$suffix}"),
        ];
    }

    private function cleanPaths(string $outputPath, string $rawDirectory): void
    {
        File::delete([$outputPath, $outputPath.'.sha256']);
        File::deleteDirectory($rawDirectory);

        foreach (glob($outputPath.'.tmp.*') ?: [] as $temporaryPath) {
            File::delete($temporaryPath);
        }
    }

    private function naojHtml(int $year, bool $complete = true): string
    {
        $rows = $this->csvRowsForYear($year);

        if (! $complete) {
            array_pop($rows);
        }

        $tableRows = '<tr><th>年月日</th><th>時刻</th><th>天体</th><th>現象</th><th>基準</th><th>備考</th></tr>';
        $tableRows .= '<tr><td>'.$year.'/01/17</td><td>12:03</td><td>太陽</td><td>雑節</td><td></td><td>土用(黄経297°)</td></tr>';

        foreach ($rows as $row) {
            [$date, $time] = explode(' ', $row['started_at']);
            $tableRows .= sprintf(
                '<tr><td>%s</td><td>%s</td><td>太陽</td><td>二十四節気</td><td></td><td>%s(黄経%d°)</td></tr>',
                str_replace('-', '/', $date),
                substr($time, 0, 5),
                $row['term_name'],
                $row['longitude_degree'],
            );
        }

        $html = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=EUC-JP"></head><body>'
            .'<h3>'.$year.'年の天象</h3><div>標準時:UT+9<sup>h</sup></div>'
            .'<table id="phenom">'.$tableRows.'</table></body></html>';

        return mb_convert_encoding($html, 'EUC-JP', 'UTF-8');
    }

    /** @return list<array{term_name:string,longitude_degree:int,started_at:string}> */
    private function csvRowsForYear(int $year): array
    {
        $handle = fopen(database_path('data/solar_terms/naoj_1899_2101.csv'), 'rb');
        $this->assertNotFalse($handle);
        $headers = fgetcsv($handle, escape: '');
        $this->assertIsArray($headers);
        $rows = [];

        try {
            while (($values = fgetcsv($handle, escape: '')) !== false) {
                if ((int) $values[0] !== $year) {
                    continue;
                }

                $rows[] = [
                    'term_name' => $values[1],
                    'longitude_degree' => (int) $values[2],
                    'started_at' => $values[3],
                ];
            }
        } finally {
            fclose($handle);
        }

        return $rows;
    }
}
