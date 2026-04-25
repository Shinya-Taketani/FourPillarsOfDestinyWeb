<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Carbon\CarbonImmutable;

/**
 * 節入りおよび天保元始暦（定気法）に基づく干支算出サービス
 */
readonly class SolarTermService
{
    /**
     * 指定された日時がどの節気に入っているかを判定し、干支情報を返す
     */
    public function getSolarInfo(CarbonImmutable $lmtDateTime): array
    {
        // DBから二十四節気のマスターを取得
        $terms = DB::table('master_solar_terms')->orderBy('longitude_degree', 'asc')->get();

        /**
         * ここでは本来、太陽黄経を精密計算するアルゴリズム（略算式）を用いますが、
         * 今回は簡易版として、節入り日の判定ロジックをシミュレーションします。
         * 泰山流では、この節入り時刻（定気法）が1秒でもずれると月柱が変わるため、
         * 最終的には「国立天文台」の暦要項に準拠したデータ参照が推奨されます。
         */
        
        // 仮の判定（本実装ではここに太陽黄経計算ロジックが入ります）
        // 例：2026年の立春は 2月4日
        
        return [
            'term_name' => '立春',
            'month_stem_id' => 1, // 甲
            'month_branch_id' => 3, // 寅
            'is_after_setsunyu' => true,
        ];
    }
}
