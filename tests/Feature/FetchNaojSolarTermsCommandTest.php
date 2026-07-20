<?php

namespace Tests\Feature;

use App\Services\SolarTermCsvReader;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FetchNaojSolarTermsCommandTest extends TestCase
{
    public function test_command_fetches_naoj_html_and_generates_valid_csv_atomically(): void
    {
        Http::fake([
            'https://eco.mtk.nao.ac.jp/cgi-bin/koyomi/cande/phenomena_sy.cgi*' => Http::response(
                $this->naojHtml(2026),
                200,
                ['Content-Type' => 'text/html; charset=EUC-JP'],
            ),
        ]);

        $outputPath = storage_path('framework/testing/naoj_command_2026.csv');
        @unlink($outputPath);
        @unlink($outputPath.'.sha256');

        try {
            $this->artisan('calendar:fetch-naoj-solar-terms', [
                '--from' => 2026,
                '--to' => 2026,
                '--output' => $outputPath,
                '--delay' => 0,
            ])->assertSuccessful();

            $rows = app(SolarTermCsvReader::class)->read($outputPath, 2026, 2026, $outputPath.'.sha256');
            $this->assertCount(24, $rows);
            $this->assertSame('2026-02-04 05:02:00', collect($rows)->firstWhere('term_name', '立春')['started_at']);
            Http::assertSentCount(1);
            Http::assertSent(static fn (Request $request): bool => $request->url() === 'https://eco.mtk.nao.ac.jp/cgi-bin/koyomi/cande/phenomena_sy.cgi?year=2026');
        } finally {
            @unlink($outputPath);
            @unlink($outputPath.'.sha256');
        }
    }

    public function test_command_does_not_overwrite_existing_csv_when_a_year_is_incomplete(): void
    {
        Http::fake([
            'https://eco.mtk.nao.ac.jp/cgi-bin/koyomi/cande/phenomena_sy.cgi*' => Http::response(
                $this->naojHtml(2026, complete: false),
                200,
                ['Content-Type' => 'text/html; charset=EUC-JP'],
            ),
        ]);

        $outputPath = storage_path('framework/testing/naoj_command_incomplete.csv');
        file_put_contents($outputPath, 'existing verified data');

        try {
            $this->artisan('calendar:fetch-naoj-solar-terms', [
                '--from' => 2026,
                '--to' => 2026,
                '--output' => $outputPath,
                '--delay' => 0,
            ])->assertFailed();

            $this->assertSame('existing verified data', file_get_contents($outputPath));
            $this->assertFileDoesNotExist($outputPath.'.sha256');
        } finally {
            @unlink($outputPath);
            @unlink($outputPath.'.sha256');
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

        $html = '<html><body><h3>'.$year.'年の天象</h3><div>標準時:UT+9<sup>h</sup></div>'
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
