<?php

namespace App\Exceptions;

use RuntimeException;

class CalendarDataUnavailableException extends RuntimeException
{
    public static function forLichun(int $year): self
    {
        return new self("指定年の採用済み立春データが未登録です: {$year}年");
    }

    public static function forMonthBoundary(string $dateTime): self
    {
        return new self("指定日時以前の採用済み正節データが未登録です: {$dateTime}");
    }
}
