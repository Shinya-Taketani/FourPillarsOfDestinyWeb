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
    /**
     * 標準時と経度からLMT補正後の時間を取得
     * * @param CarbonImmutable $dateTime 出生日時
     * @param float $longitude 出生地の経度（例: 135.00）
     * @return CarbonImmutable 補正後の日時
     */
    public function calculate(CarbonImmutable $dateTime, float $longitude): CarbonImmutable
    {
        // 基準経度（日本標準時：東経135度）
        $baseLongitude = 135.0;

        // 経度差を計算
        $diffLongitude = $longitude - $baseLongitude;

        // 1度につき4分（240秒）の時差が発生する
        // PHP 8.4の型安全性を活かした計算
        $offsetSeconds = (int) round($diffLongitude * 240);

        // 秒単位で加減算して返す
        return $dateTime->addSeconds($offsetSeconds);
    }

    /**
     * PHP 8.4 Property Hooks のデモ用（内部利用可能）
     * 経度から時差の文字列を生成する
     */
    public function getOffsetDescription(float $longitude): string
    {
        $offset = ($longitude - 135.0) * 4;
        return $offset >= 0 ? "+{$offset}分" : "{$offset}分";
    }
}
