<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\CarbonImmutable;

/**
 * LMT（Local Mean Time：地方平均太陽時）を算出するサービス
 * 135度（明石）との経度差を、1度につき4分の割合で加減調整する。
 */
readonly class LmtCalculatorService
{
    private const JST_STANDARD_LONGITUDE = 135.0;

    private const SECONDS_PER_LONGITUDE_DEGREE = 240;

    /**
     * JST の出生日時と経度から LMT 補正後の日時を取得する。
     *
     * @param  CarbonImmutable  $dateTime  出生日時（現行仕様では JST）
     * @param  float  $longitude  出生地の経度（例: 135.00）
     * @return CarbonImmutable LMT 補正後の日時
     */
    public function calculate(CarbonImmutable $dateTime, float $longitude): CarbonImmutable
    {
        $diffLongitude = $longitude - self::JST_STANDARD_LONGITUDE;
        $offsetSeconds = (int) round($diffLongitude * self::SECONDS_PER_LONGITUDE_DEGREE);

        return $dateTime->addSeconds($offsetSeconds);
    }

    /**
     * PHP 8.4 Property Hooks のデモ用（内部利用可能）
     * 経度から時差の文字列を生成する
     */
    public function getOffsetDescription(float $longitude): string
    {
        $offset = ($longitude - self::JST_STANDARD_LONGITUDE) * 4;

        return $offset >= 0 ? "+{$offset}分" : "{$offset}分";
    }
}
