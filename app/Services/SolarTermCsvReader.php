<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Calendar\NaojSolarTermClient;
use App\Services\Calendar\SolarTermCsvWriter;
use App\Support\SolarTermCatalog;
use DateTimeImmutable;
use RuntimeException;

/**
 * @phpstan-type SolarTermCsvRow array{
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
 *     raw_content_hash:string,
 *     verification_status:string,
 *     note:string
 * }
 */
final class SolarTermCsvReader
{
    /** @return list<SolarTermCsvRow> */
    public function read(string $csvPath, int $fromYear, int $toYear, ?string $checksumPath = null): array
    {
        if ($fromYear > $toYear) {
            throw new RuntimeException('CSV検証年の範囲が不正です。');
        }

        if (! is_file($csvPath) || ! is_readable($csvPath)) {
            throw new RuntimeException("二十四節気CSVを読み込めません: {$csvPath}");
        }

        if ($checksumPath !== null) {
            $this->verifyChecksum($csvPath, $checksumPath);
        }

        $handle = fopen($csvPath, 'rb');

        if ($handle === false) {
            throw new RuntimeException("二十四節気CSVを開けません: {$csvPath}");
        }

        try {
            $headers = fgetcsv($handle, escape: '');

            if ($headers !== SolarTermCsvWriter::HEADERS) {
                throw new RuntimeException('二十四節気CSVのヘッダーが定義と一致しません。');
            }

            $rows = [];
            $lineNumber = 1;

            while (($values = fgetcsv($handle, escape: '')) !== false) {
                $lineNumber++;

                if (count($values) !== count(SolarTermCsvWriter::HEADERS)) {
                    throw new RuntimeException("二十四節気CSV {$lineNumber}行目の列数が不正です。");
                }

                /** @var array<string,string> $row */
                $row = array_combine(SolarTermCsvWriter::HEADERS, $values);
                $rows[] = $this->validateRow($row, $lineNumber, $fromYear, $toYear);
            }
        } finally {
            fclose($handle);
        }

        $this->validateCompleteness($rows, $fromYear, $toYear);

        return $rows;
    }

    private function verifyChecksum(string $csvPath, string $checksumPath): void
    {
        if (! is_file($checksumPath) || ! is_readable($checksumPath)) {
            throw new RuntimeException("SHA-256ファイルを読み込めません: {$checksumPath}");
        }

        $contents = trim((string) file_get_contents($checksumPath));
        $expected = preg_split('/\s+/', $contents)[0] ?? '';
        $actual = hash_file('sha256', $csvPath);

        if ($actual === false || preg_match('/^[a-f0-9]{64}$/', $expected) !== 1 || ! hash_equals($expected, $actual)) {
            throw new RuntimeException('二十四節気CSVのSHA-256が一致しません。');
        }
    }

    /**
     * @param  array<string,string>  $row
     * @return SolarTermCsvRow
     */
    private function validateRow(array $row, int $lineNumber, int $fromYear, int $toYear): array
    {
        $catalog = SolarTermCatalog::indexedByName();
        $year = filter_var($row['year'], FILTER_VALIDATE_INT);
        $degree = filter_var($row['longitude_degree'], FILTER_VALIDATE_INT);
        $definition = $catalog[$row['term_name']] ?? null;
        $dateTime = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $row['started_at']);
        $dateErrors = DateTimeImmutable::getLastErrors();
        $accessedOn = DateTimeImmutable::createFromFormat('!Y-m-d', $row['source_accessed_on']);
        $accessedOnErrors = DateTimeImmutable::getLastErrors();

        if ($year === false || $year < $fromYear || $year > $toYear) {
            throw new RuntimeException("二十四節気CSV {$lineNumber}行目の年が対象範囲外です。");
        }

        if ($definition === null || $degree === false || $definition['longitude_degree'] !== $degree) {
            throw new RuntimeException("二十四節気CSV {$lineNumber}行目の節気名と黄経が一致しません。");
        }

        if ($dateTime === false || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))
            || $dateTime->format('Y-m-d H:i:s') !== $row['started_at'] || (int) $dateTime->format('Y') !== $year
            || ! str_ends_with($row['started_at'], ':00')) {
            throw new RuntimeException("二十四節気CSV {$lineNumber}行目の日時が不正です。");
        }

        if ($accessedOn === false
            || ($accessedOnErrors !== false && ($accessedOnErrors['warning_count'] > 0 || $accessedOnErrors['error_count'] > 0))
            || $accessedOn->format('Y-m-d') !== $row['source_accessed_on']) {
            throw new RuntimeException("二十四節気CSV {$lineNumber}行目の取得日が不正です。");
        }

        if (! in_array($row['adopted'], ['true', 'false'], true)
            || ! in_array($row['verification_status'], ['imported', 'verified', 'rejected'], true)) {
            throw new RuntimeException("二十四節気CSV {$lineNumber}行目の採用・検証状態が不正です。");
        }

        $adopted = $row['adopted'] === 'true';

        if (($row['verification_status'] === 'rejected') === $adopted) {
            throw new RuntimeException("二十四節気CSV {$lineNumber}行目の採用状態と検証状態が矛盾しています。");
        }

        if ($row['timezone'] !== 'Asia/Tokyo'
            || $row['calendar_system'] !== 'modern_astronomical'
            || $row['source_rank'] !== 'S2'
            || $row['precision_level'] !== 'minute'
            || $row['source_citation_text'] !== '国立天文台ホームページより引用'
            || $row['source_title'] !== '国立天文台 暦計算室 二十四節気・雑節 長期版'
            || $row['source_url'] !== NaojSolarTermClient::citationUrl($year)
            || preg_match('/^[a-f0-9]{64}$/', $row['raw_content_hash']) !== 1) {
            throw new RuntimeException("二十四節気CSV {$lineNumber}行目の出典メタデータが正式データ要件を満たしません。");
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
            'adopted' => $adopted,
            'precision_level' => $row['precision_level'],
            'source_accessed_on' => $row['source_accessed_on'],
            'source_citation_text' => $row['source_citation_text'],
            'raw_content_hash' => $row['raw_content_hash'],
            'verification_status' => $row['verification_status'],
            'note' => $row['note'],
        ];
    }

    /** @param list<SolarTermCsvRow> $rows */
    private function validateCompleteness(array $rows, int $fromYear, int $toYear): void
    {
        $byYear = [];

        foreach ($rows as $row) {
            $key = $row['year'].'|'.$row['term_name'];

            if (isset($byYear[$row['year']]['keys'][$key])) {
                throw new RuntimeException("{$row['year']}年の{$row['term_name']}が重複しています。");
            }

            $previous = $byYear[$row['year']]['last_started_at'] ?? null;
            if ($previous !== null && $row['started_at'] <= $previous) {
                throw new RuntimeException("{$row['year']}年の節気日時が昇順ではありません。");
            }

            $byYear[$row['year']]['keys'][$key] = true;
            $byYear[$row['year']]['last_started_at'] = $row['started_at'];
            $byYear[$row['year']]['rows'][] = $row;
        }

        $catalog = SolarTermCatalog::indexedByName();

        for ($year = $fromYear; $year <= $toYear; $year++) {
            /** @var list<SolarTermCsvRow> $yearRows */
            $yearRows = $byYear[$year]['rows'] ?? [];
            $majorCount = 0;
            $middleCount = 0;

            foreach ($yearRows as $row) {
                if ($catalog[$row['term_name']]['term_type'] === 'major_term') {
                    $majorCount++;
                } else {
                    $middleCount++;
                }
            }

            if (count($yearRows) !== 24 || $majorCount !== 12 || $middleCount !== 12) {
                throw new RuntimeException("{$year}年の二十四節気が不完全です（全体: ".count($yearRows)."、正節: {$majorCount}、中気: {$middleCount}）。");
            }

            if (count(array_unique(array_column($yearRows, 'raw_content_hash'))) !== 1) {
                throw new RuntimeException("{$year}年のraw content hashが統一されていません。");
            }
        }

        $expectedCount = ($toYear - $fromYear + 1) * 24;
        if (count($rows) !== $expectedCount) {
            throw new RuntimeException('二十四節気CSVの全体件数が不正です。');
        }
    }
}
