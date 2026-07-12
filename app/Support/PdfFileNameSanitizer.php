<?php

declare(strict_types=1);

namespace App\Support;

final class PdfFileNameSanitizer
{
    public static function part(?string $value, string $fallback = 'unknown'): string
    {
        $value = trim((string) $value);
        $value = preg_replace('/[\\\\\/:*?"<>|\r\n\x00-\x1F\x7F]+/u', '_', $value) ?? '';
        $value = trim($value, " _.\t\n\r\0\x0B");

        return $value !== '' ? $value : $fallback;
    }
}
