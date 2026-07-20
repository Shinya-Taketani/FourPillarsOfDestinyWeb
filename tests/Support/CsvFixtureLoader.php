<?php

declare(strict_types=1);

namespace Tests\Support;

use RuntimeException;

final class CsvFixtureLoader
{
    /**
     * @return list<array<string, string>>
     */
    public static function load(string $filename): array
    {
        $path = base_path('tests/fixtures/'.$filename);

        if (! is_file($path)) {
            throw new RuntimeException("CSV fixture not found: {$path}");
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException("CSV fixture cannot be opened: {$path}");
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            throw new RuntimeException("CSV fixture has no header: {$path}");
        }

        $rows = [];
        while (($row = fgetcsv($handle)) !== false) {
            if ($row === [null] || $row === []) {
                continue;
            }

            if (count($row) !== count($header)) {
                fclose($handle);
                throw new RuntimeException("CSV fixture column count mismatch: {$path}");
            }

            $rows[] = array_combine($header, $row);
        }

        fclose($handle);

        return $rows;
    }
}
