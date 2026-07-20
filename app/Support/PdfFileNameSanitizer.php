<?php

declare(strict_types=1);

namespace App\Support;

final class PdfFileNameSanitizer
{
    private const MAX_PART_LENGTH = 80;

    public static function part(?string $value, string $fallback = 'unknown'): string
    {
        $value = trim((string) $value);
        $value = preg_replace('/[\\\\\/:*?"<>|\r\n\t\x00-\x1F\x7F]+/u', '_', $value) ?? '';
        $value = preg_replace('/[^\p{L}\p{N}\p{M} ._-]+/u', '_', $value) ?? '';
        $value = preg_replace('/\s+/u', ' ', $value) ?? '';
        $value = preg_replace('/_+/u', '_', $value) ?? '';
        $value = trim($value, " _.\t\n\r\0\x0B");

        if ($value === '') {
            return $fallback;
        }

        return mb_substr($value, 0, self::MAX_PART_LENGTH);
    }

    public static function appraisal(?string $name): string
    {
        return '運命鑑定書_'.self::part($name).'.pdf';
    }

    public static function compatibility(?string $person1Name, ?string $person2Name): string
    {
        return '相性鑑定書_'.self::part($person1Name, 'person1').'_'.self::part($person2Name, 'person2').'.pdf';
    }
}
