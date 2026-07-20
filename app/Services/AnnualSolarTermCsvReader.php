<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\SolarTermCatalog;
use DateTimeImmutable;
use RuntimeException;

/**
 * @phpstan-type AnnualSolarTermCsvRow array{
 *     year:int,
 *     term_name:string,
 *     longitude_degree:int,
 *     started_at:string,
 *     timezone:string,
 *     calendar_system:string,
 *     source_title:string,
 *     source_url:string,
 *     source_rank:string,
 *     adopted:bool,
 *     precision_level:string,
 *     source_accessed_on:string,
 *     source_citation_text:string,
 *     verification_status:string,
 *     note:string
 * }
 */
final class AnnualSolarTermCsvReader
{
    /** @var list<string> */
    public const HEADERS = [
        'year', 'term_name', 'longitude_degree', 'started_at', 'timezone',
        'calendar_system', 'source_title', 'source_url', 'source_rank', 'adopted',
        'precision_level', 'source_accessed_on', 'source_citation_text',
        'verification_status', 'note',
    ];

    /** @var list<int> */
    private const REQUIRED_YEARS = [1971, 1980, 2026];

    /** @var array<int,array{title:string,url:string}> */
    private const SOURCES = [
        1971 => [
            'title' => '昭和46年（1971）暦要項 二十四節気',
            'url' => 'https://eco.mtk.nao.ac.jp/koyomi/yoko/pdf/yoko1971.pdf',
        ],
        1980 => [
            'title' => '昭和55年（1980）暦要項 二十四節気',
            'url' => 'https://eco.mtk.nao.ac.jp/koyomi/yoko/pdf/yoko1980.pdf',
        ],
        2026 => [
            'title' => '令和8年（2026）暦要項 二十四節気および雑節',
            'url' => 'https://eco.mtk.nao.ac.jp/koyomi/yoko/2026/rekiyou262.html',
        ],
    ];

    /** @return list<AnnualSolarTermCsvRow> */
    public function read(string $csvPath, string $checksumPath): array
    {
        $this->verifyChecksum($csvPath, $checksumPath);
        $handle = fopen($csvPath, 'rb');

        if ($handle === false) {
            throw new RuntimeException("年次公式二十四節気CSVを開けません: {$csvPath}");
        }

        try {
            if (fgetcsv($handle, escape: '') !== self::HEADERS) {
                throw new RuntimeException('年次公式二十四節気CSVのヘッダーが定義と一致しません。');
            }

            $rows = [];
            $lineNumber = 1;

            while (($values = fgetcsv($handle, escape: '')) !== false) {
                $lineNumber++;

                if (count($values) !== count(self::HEADERS)) {
                    throw new RuntimeException("年次公式二十四節気CSV {$lineNumber}行目の列数が不正です。");
                }

                /** @var array<string,string> $row */
                $row = array_combine(self::HEADERS, $values);
                $rows[] = $this->validateRow($row, $lineNumber);
            }
        } finally {
            fclose($handle);
        }

        $this->validateCompleteness($rows);

        return $rows;
    }

    private function verifyChecksum(string $csvPath, string $checksumPath): void
    {
        if (! is_file($csvPath) || ! is_readable($csvPath)
            || ! is_file($checksumPath) || ! is_readable($checksumPath)) {
            throw new RuntimeException('年次公式二十四節気CSVまたはSHA-256ファイルを読み込めません。');
        }

        $expected = preg_split('/\s+/', trim((string) file_get_contents($checksumPath)))[0] ?? '';
        $actual = hash_file('sha256', $csvPath);

        if ($actual === false || preg_match('/^[a-f0-9]{64}$/', $expected) !== 1 || ! hash_equals($expected, $actual)) {
            throw new RuntimeException('年次公式二十四節気CSVのSHA-256が一致しません。');
        }
    }

    /**
     * @param  array<string,string>  $row
     * @return AnnualSolarTermCsvRow
     */
    private function validateRow(array $row, int $lineNumber): array
    {
        $catalog = SolarTermCatalog::indexedByName();
        $year = filter_var($row['year'], FILTER_VALIDATE_INT);
        $degree = filter_var($row['longitude_degree'], FILTER_VALIDATE_INT);
        $definition = $catalog[$row['term_name']] ?? null;
        $startedAt = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $row['started_at']);
        $startedAtErrors = DateTimeImmutable::getLastErrors();
        $accessedOn = DateTimeImmutable::createFromFormat('!Y-m-d', $row['source_accessed_on']);
        $accessedOnErrors = DateTimeImmutable::getLastErrors();

        if ($year === false || ! in_array($year, self::REQUIRED_YEARS, true)) {
            throw new RuntimeException("年次公式二十四節気CSV {$lineNumber}行目の年が対象外です。");
        }

        if ($definition === null || $degree === false || $definition['longitude_degree'] !== $degree) {
            throw new RuntimeException("年次公式二十四節気CSV {$lineNumber}行目の節気名と黄経が一致しません。");
        }

        if ($startedAt === false
            || ($startedAtErrors !== false && ($startedAtErrors['warning_count'] > 0 || $startedAtErrors['error_count'] > 0))
            || $startedAt->format('Y-m-d H:i:s') !== $row['started_at']
            || (int) $startedAt->format('Y') !== $year
            || ! str_ends_with($row['started_at'], ':00')) {
            throw new RuntimeException("年次公式二十四節気CSV {$lineNumber}行目の日時が不正です。");
        }

        if ($accessedOn === false
            || ($accessedOnErrors !== false && ($accessedOnErrors['warning_count'] > 0 || $accessedOnErrors['error_count'] > 0))
            || $accessedOn->format('Y-m-d') !== $row['source_accessed_on']) {
            throw new RuntimeException("年次公式二十四節気CSV {$lineNumber}行目の取得日が不正です。");
        }

        if ($row['timezone'] !== 'Asia/Tokyo'
            || $row['calendar_system'] !== 'modern_astronomical'
            || $row['source_rank'] !== 'S1'
            || $row['adopted'] !== 'true'
            || $row['precision_level'] !== 'minute'
            || $row['verification_status'] !== 'verified'
            || $row['source_citation_text'] !== '国立天文台ホームページより引用'
            || $row['source_title'] !== self::SOURCES[$year]['title']
            || $row['source_url'] !== self::SOURCES[$year]['url']) {
            throw new RuntimeException("年次公式二十四節気CSV {$lineNumber}行目の出典メタデータが正式データ要件を満たしません。");
        }

        return [
            'year' => $year,
            'term_name' => $row['term_name'],
            'longitude_degree' => $degree,
            'started_at' => $row['started_at'],
            'timezone' => $row['timezone'],
            'calendar_system' => $row['calendar_system'],
            'source_title' => $row['source_title'],
            'source_url' => $row['source_url'],
            'source_rank' => $row['source_rank'],
            'adopted' => true,
            'precision_level' => $row['precision_level'],
            'source_accessed_on' => $row['source_accessed_on'],
            'source_citation_text' => $row['source_citation_text'],
            'verification_status' => $row['verification_status'],
            'note' => $row['note'],
        ];
    }

    /** @param list<AnnualSolarTermCsvRow> $rows */
    private function validateCompleteness(array $rows): void
    {
        $catalog = SolarTermCatalog::indexedByName();
        $byYear = [];

        foreach ($rows as $row) {
            if (isset($byYear[$row['year']]['names'][$row['term_name']])) {
                throw new RuntimeException("{$row['year']}年の{$row['term_name']}が重複しています。");
            }

            $previous = $byYear[$row['year']]['last_started_at'] ?? null;
            if ($previous !== null && $row['started_at'] <= $previous) {
                throw new RuntimeException("{$row['year']}年の年次公式二十四節気が時系列順ではありません。");
            }

            $byYear[$row['year']]['names'][$row['term_name']] = true;
            $byYear[$row['year']]['last_started_at'] = $row['started_at'];
            $byYear[$row['year']]['rows'][] = $row;
        }

        foreach (self::REQUIRED_YEARS as $year) {
            $yearRows = $byYear[$year]['rows'] ?? [];
            $majorCount = count(array_filter(
                $yearRows,
                static fn (array $row): bool => $catalog[$row['term_name']]['term_type'] === 'major_term',
            ));

            if (count($yearRows) !== 24 || $majorCount !== 12) {
                throw new RuntimeException("{$year}年の年次公式二十四節気が不完全です。");
            }
        }

        if (count($rows) !== 72) {
            throw new RuntimeException('年次公式二十四節気CSVの全体件数が不正です。');
        }
    }
}
