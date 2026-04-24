<?php

namespace App\Services;

readonly class LmtCalculator
{
    public static function calculateLmt(float $longitude, \DateTime $jst): \DateTime
    {
        $diffDegrees = $longitude - 135.0;
        $correctionMinutes = $diffDegrees * 4;
        $correctionSeconds = $correctionMinutes * 60;
        $lmt = clone $jst;
        $lmt->modify("{$correctionSeconds} seconds");
        return $lmt;
    }
}
