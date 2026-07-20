<?php

declare(strict_types=1);

namespace App\Services\Calendar;

use Illuminate\Support\Facades\File;
use RuntimeException;

final class SolarTermCsvWriter
{
    public const HEADERS = [
        'year',
        'term_name',
        'longitude_degree',
        'started_at',
        'timezone',
        'calendar_system',
        'source_title',
        'source_url',
        'source_rank',
        'adopted',
        'precision_level',
        'source_accessed_on',
        'source_citation_text',
        'raw_content_hash',
        'verification_status',
        'note',
    ];

    /** @param list<array<string,int|string>> $rows */
    public function writeAtomically(string $outputPath, array $rows): string
    {
        File::ensureDirectoryExists(dirname($outputPath));
        $temporaryPath = $outputPath.'.tmp.'.getmypid();
        $temporaryChecksumPath = $temporaryPath.'.sha256';
        $handle = fopen($temporaryPath, 'wb');

        if ($handle === false) {
            throw new RuntimeException("一時CSVを作成できません: {$temporaryPath}");
        }

        try {
            fputcsv($handle, self::HEADERS, ',', '"', '');

            foreach ($rows as $row) {
                $values = [];

                foreach (self::HEADERS as $header) {
                    if (! array_key_exists($header, $row)) {
                        throw new RuntimeException("CSV列が不足しています: {$header}");
                    }

                    $values[] = $row[$header];
                }

                fputcsv($handle, $values, ',', '"', '');
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

        return $checksum;
    }
}
