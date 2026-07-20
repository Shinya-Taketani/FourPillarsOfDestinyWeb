<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\NaojSolarTermPageParser;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class FetchNaojSolarTermsCommand extends Command
{
    private const BASE_URL = 'https://eco.mtk.nao.ac.jp/cgi-bin/koyomi/cande/phenomena_sy.cgi';

    private const SOURCE_TITLE = '国立天文台 暦計算室 二十四節気・雑節 長期版';

    /** @var string */
    protected $signature = 'calendar:fetch-naoj-solar-terms
        {--from=1899 : 取得開始年}
        {--to=2101 : 取得終了年}
        {--output=database/data/solar_terms/naoj_1899_2101.csv : 出力CSVパス}
        {--delay=200 : 年ごとの取得間隔（ミリ秒）}';

    /** @var string */
    protected $description = '国立天文台の長期版から検証済み二十四節気CSVを生成する';

    public function handle(NaojSolarTermPageParser $parser): int
    {
        $from = filter_var($this->option('from'), FILTER_VALIDATE_INT);
        $to = filter_var($this->option('to'), FILTER_VALIDATE_INT);
        $delay = filter_var($this->option('delay'), FILTER_VALIDATE_INT);

        if ($from === false || $to === false || $delay === false || $from < 1 || $to > 2999 || $from > $to || $delay < 0) {
            $this->error('from/toは1〜2999の範囲でfrom <= to、delayは0以上を指定してください。');

            return self::FAILURE;
        }

        $outputOption = (string) $this->option('output');
        $outputPath = str_starts_with($outputOption, '/') ? $outputOption : base_path($outputOption);
        $allRows = [];
        $failedYears = [];

        for ($year = $from; $year <= $to; $year++) {
            try {
                $url = self::BASE_URL.'?'.http_build_query(['year' => $year]);
                $response = Http::accept('text/html')
                    ->withUserAgent('FourPillarsOfDestinyWeb solar-term dataset generator')
                    ->timeout(20)
                    ->retry(
                        [500, 1000, 2000],
                        0,
                        static function (Throwable $exception): bool {
                            if ($exception instanceof ConnectionException) {
                                return true;
                            }

                            return $exception instanceof RequestException
                                && ($exception->response->status() === 429 || $exception->response->serverError());
                        },
                        throw: false,
                    )
                    ->get(self::BASE_URL, ['year' => $year]);

                $response->throw();
                $this->assertSupportedEncoding($response->header('Content-Type'));

                foreach ($parser->parse($response->body(), $year) as $event) {
                    $allRows[] = [
                        ...$event,
                        'timezone' => 'Asia/Tokyo',
                        'calendar_system' => 'modern_astronomical',
                        'source_title' => self::SOURCE_TITLE,
                        'source_url' => $url,
                        'source_rank' => 'S',
                        'adopted' => 'true',
                        'note' => '国立天文台長期版の中央標準時。秒は00秒、24:00表記は翌日00:00に正規化。',
                    ];
                }

                $this->line("{$year}: 24件取得");
            } catch (Throwable $exception) {
                $failedYears[] = $year;
                $this->error("{$year}: {$exception->getMessage()}");
            }

            if ($year < $to && $delay > 0) {
                usleep($delay * 1000);
            }
        }

        $successYears = ($to - $from + 1) - count($failedYears);

        if ($failedYears !== []) {
            $this->error(sprintf(
                'CSVは更新しません。成功年数: %d、失敗年数: %d、行数: %d',
                $successYears,
                count($failedYears),
                count($allRows),
            ));

            return self::FAILURE;
        }

        $this->writeAtomically($outputPath, $allRows);
        $this->info(sprintf(
            '生成完了: %s（成功年数: %d、失敗年数: 0、行数: %d）',
            $outputPath,
            $successYears,
            count($allRows),
        ));

        return self::SUCCESS;
    }

    private function assertSupportedEncoding(?string $contentType): void
    {
        if ($contentType === null || preg_match('/charset=([^;\s]+)/i', $contentType, $matches) !== 1) {
            return;
        }

        if (! in_array(strtoupper($matches[1]), ['EUC-JP', 'UTF-8'], true)) {
            throw new RuntimeException("未対応の文字コードです: {$matches[1]}");
        }
    }

    /** @param list<array<string,int|string>> $rows */
    private function writeAtomically(string $outputPath, array $rows): void
    {
        File::ensureDirectoryExists(dirname($outputPath));
        $temporaryPath = $outputPath.'.tmp.'.getmypid();
        $temporaryChecksumPath = $temporaryPath.'.sha256';
        $handle = fopen($temporaryPath, 'wb');

        if ($handle === false) {
            throw new RuntimeException("一時CSVを作成できません: {$temporaryPath}");
        }

        try {
            fputcsv($handle, [
                'year', 'term_name', 'longitude_degree', 'started_at', 'timezone', 'calendar_system',
                'source_title', 'source_url', 'source_rank', 'adopted', 'note',
            ], ',', '"', '');

            foreach ($rows as $row) {
                fputcsv($handle, array_values($row), ',', '"', '');
            }
        } finally {
            fclose($handle);
        }

        $checksum = hash_file('sha256', $temporaryPath);

        if ($checksum === false) {
            File::delete($temporaryPath);
            throw new RuntimeException('生成CSVのSHA-256を計算できませんでした。');
        }

        File::put($temporaryChecksumPath, $checksum.'  '.basename($outputPath).PHP_EOL);

        if (! rename($temporaryPath, $outputPath)) {
            File::delete([$temporaryPath, $temporaryChecksumPath]);
            throw new RuntimeException("CSVを配置できません: {$outputPath}");
        }

        if (! rename($temporaryChecksumPath, $outputPath.'.sha256')) {
            throw new RuntimeException("SHA-256ファイルを配置できません: {$outputPath}.sha256");
        }
    }
}
