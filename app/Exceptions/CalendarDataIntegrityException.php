<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class CalendarDataIntegrityException extends RuntimeException
{
    public static function forDuplicateAdoptedEvent(string $termName, int $year): self
    {
        return new self("同一年・同一節気に複数の正式採用データがあります: {$year}年 {$termName}");
    }

    public static function forDuplicateAdoptedYear(int $year): self
    {
        return new self("同一年・同一節気に複数の正式採用データがあります: {$year}年");
    }
}
