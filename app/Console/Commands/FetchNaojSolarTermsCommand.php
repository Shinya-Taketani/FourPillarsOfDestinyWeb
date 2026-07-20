<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Calendar\NaojSolarTermClient;
use App\Services\Calendar\SolarTermCsvWriter;
use App\Services\NaojSolarTermPageParser;
use App\Support\NaojSolarTermAuditCatalog;
use App\Support\SolarTermCatalog;
use Illuminate\Console\Command;
use Throwable;

class FetchNaojSolarTermsCommand extends Command
{
    private const SOURCE_TITLE = '国立天文台 暦計算室 二十四節気・雑節 長期版';

    /** @var string */
    protected $signature = 'calendar:fetch-naoj-solar-terms
        {--from=1899 : 取得開始年}
        {--to=2101 : 取得終了年}
        {--output=database/data/solar_terms/naoj_1899_2101.csv : 出力CSVパス}
        {--raw-dir=database/data/solar_terms/raw : 取得済みraw HTMLの保存先}
        {--refresh : rawキャッシュを使用せず再取得する}
        {--delay=200 : 年ごとの取得間隔（ミリ秒）}';

    /** @var string */
    protected $description = '国立天文台の長期版から検証済み二十四節気CSVを生成する';

    public function handle(
        NaojSolarTermClient $client,
        NaojSolarTermPageParser $parser,
        SolarTermCsvWriter $writer,
    ): int {
        $from = filter_var($this->option('from'), FILTER_VALIDATE_INT);
        $to = filter_var($this->option('to'), FILTER_VALIDATE_INT);
        $delay = filter_var($this->option('delay'), FILTER_VALIDATE_INT);

        if ($from === false || $to === false || $delay === false || $from < 1 || $to > 2999 || $from > $to || $delay < 0) {
            $this->error('from/toは1〜2999の範囲でfrom <= to、delayは0以上を指定してください。');

            return self::FAILURE;
        }

        $outputOption = (string) $this->option('output');
        $outputPath = str_starts_with($outputOption, '/') ? $outputOption : base_path($outputOption);
        $rawDirectoryOption = (string) $this->option('raw-dir');
        $rawDirectory = str_starts_with($rawDirectoryOption, '/') ? $rawDirectoryOption : base_path($rawDirectoryOption);
        $allRows = [];
        $failedYears = [];
        $rejectedYears = [];

        for ($year = $from; $year <= $to; $year++) {
            try {
                $raw = $client->fetch($year, $rawDirectory, (bool) $this->option('refresh'));
                $events = $parser->parse($raw['body'], $year);
                $mismatches = NaojSolarTermAuditCatalog::mismatches($year, $events);
                $hasAuditReference = isset(NaojSolarTermAuditCatalog::EXPECTED_EVENTS[$year]);
                $verificationStatus = $mismatches === []
                    ? ($hasAuditReference ? 'verified' : 'imported')
                    : 'rejected';
                $adopted = $verificationStatus !== 'rejected';

                if ($mismatches !== []) {
                    $rejectedYears[] = $year;
                }

                $note = $this->buildNote($year, $mismatches);

                foreach ($events as $event) {
                    $allRows[] = [
                        ...$event,
                        'timezone' => 'Asia/Tokyo',
                        'calendar_system' => 'modern_astronomical',
                        'source_title' => self::SOURCE_TITLE,
                        'source_url' => $raw['source_url'],
                        'source_rank' => 'S2',
                        'adopted' => $adopted ? 'true' : 'false',
                        'precision_level' => 'minute',
                        'source_accessed_on' => $raw['source_accessed_on'],
                        'source_citation_text' => '国立天文台ホームページより引用',
                        'raw_content_hash' => $raw['raw_content_hash'],
                        'verification_status' => $verificationStatus,
                        'note' => $note,
                    ];
                }

                $cacheLabel = $raw['from_cache'] ? 'raw再利用' : '取得';
                $this->line("{$year}: 24件{$cacheLabel} / {$verificationStatus}");
            } catch (Throwable $exception) {
                $failedYears[] = $year;
                $this->error("{$year}: {$exception->getMessage()}");
            }

            if ($year < $to && $delay > 0 && isset($raw) && ! $raw['from_cache']) {
                usleep($delay * 1000);
            }

            unset($raw);
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

        $checksum = $writer->writeAtomically($outputPath, $allRows);
        $catalog = SolarTermCatalog::indexedByName();
        $majorCount = count(array_filter(
            $allRows,
            static fn (array $row): bool => $catalog[$row['term_name']]['term_type'] === 'major_term',
        ));
        $middleCount = count($allRows) - $majorCount;

        $this->newLine();
        $this->table(['項目', '結果'], [
            ['対象年数', (string) ($to - $from + 1)],
            ['成功年数', (string) $successYears],
            ['失敗年数', '0'],
            ['総件数', (string) count($allRows)],
            ['正節件数', (string) $majorCount],
            ['中気件数', (string) $middleCount],
            ['監査保留年', $rejectedYears === [] ? 'なし' : implode(', ', $rejectedYears)],
            ['出力先', $outputPath],
            ['SHA-256', $checksum],
        ]);

        return self::SUCCESS;
    }

    /**
     * @param  list<array{term_name:string,actual:string,expected:string}>  $mismatches
     */
    private function buildNote(int $year, array $mismatches): string
    {
        $base = '国立天文台長期版の中央標準時。公式精度は分、秒は00秒。24:00表記は翌日00:00に正規化。';

        if ($mismatches === []) {
            return $base;
        }

        $details = [];

        foreach ($mismatches as $mismatch) {
            $details[] = sprintf(
                '%s: 長期版=%s / 年次監査値=%s',
                $mismatch['term_name'],
                $mismatch['actual'],
                $mismatch['expected'],
            );
        }

        return $base.' 年次監査値と不一致のため採用保留。監査元: '
            .NaojSolarTermAuditCatalog::SOURCE_URLS[$year].'。'.implode(' / ', $details);
    }
}
