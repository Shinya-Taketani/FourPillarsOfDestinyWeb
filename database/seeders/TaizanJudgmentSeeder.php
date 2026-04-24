<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TaizanJudgmentSeeder extends Seeder
{
    public function run(): void
    {
        // --- 1. 通変星の性格・運勢判定 (完結) ---
        $tenGods = [
            ['id' => 1, 'name' => '比肩', 'description' => '独立心が強く、自分自身の力で人生を切り拓くタイプ。頑固だが意志が強い。'],
            ['id' => 2, 'name' => '劫財', 'description' => '外面はソフトだが内面は意地っ張り。社交性があるが、金銭の流出に注意が必要。'],
            ['id' => 3, 'name' => '食神', 'description' => '衣食住に困らず、楽天的。表現力が豊かで、人生を楽しむ才能がある。'],
            ['id' => 4, 'name' => '傷官', 'description' => '感受性が鋭く技術・芸術の才能あり。完璧主義で、言葉によるトラブルに注意。'],
            ['id' => 5, 'name' => '偏財', 'description' => '回転財の星。商売上手で社交的。多趣味で活動範囲が広い。'],
            ['id' => 6, 'name' => '正財', 'description' => '蓄積財の星。誠実で保守的。着実に資産と信頼を築く堅実派。'],
            ['id' => 7, 'name' => '偏官', 'description' => '行動派で親分肌。決断が早いが、強引になりがちな面もある。'],
            ['id' => 8, 'name' => '正官', 'description' => '理性的で品格がある。規律を重んじ、組織の中で信頼を得るタイプ。'],
            ['id' => 9, 'name' => '偏印', 'description' => '知恵とひらめきの星。個性的で多才。束縛を嫌い、自由な発想を持つ。'],
            ['id' => 10, 'name' => '印綬', 'description' => '学問と名誉の星。慈愛に満ち、伝統や知識を大切にする知的なタイプ。'],
        ];

        foreach ($tenGods as $god) {
            DB::table('master_ten_gods')->updateOrInsert(['id' => $god['id']], $god);
        }

        // --- 2. 十二運のエネルギー判定 (完結) ---
        $twelveStages = [
            ['id' => 1, 'name' => '胎', 'description' => '新しい命の芽生え。希望に溢れるが、まだ実行力は弱い状態。'],
            ['id' => 2, 'name' => '養', 'description' => '育まれる時期。素直で人から助けられやすく、円満な性格。'],
            ['id' => 3, 'name' => '長生', 'description' => '順調な成長。信頼を得やすく、発展性がある幸運な状態。'],
            ['id' => 4, 'name' => '沐浴', 'description' => '思春期のような迷い。行動的だが不安定。創造力や芸術性に富む。'],
            ['id' => 5, 'name' => '冠帯', 'description' => '成人式。意欲満々で前進する。華やかさがあるが、功焦りに注意。'],
            ['id' => 6, 'name' => '建禄', 'description' => '働き盛りの完成。最も安定した強さを持ち、社会的な成功を収める。'],
            ['id' => 7, 'name' => '帝旺', 'description' => '頂点。エネルギーは最大。強引になりやすく、謙虚さが鍵。'],
            ['id' => 8, 'name' => '衰', 'description' => '円熟味。保守的で冷静な判断ができる。経験を活かす時期。'],
            ['id' => 9, 'name' => '病', 'description' => '精神的な広がり。体力は落ちるが、感受性や空想力が高まる。'],
            ['id' => 10, 'name' => '死', 'description' => '活動の停止。静止して深く考える。探究心が強く、専門分野に強い。'],
            ['id' => 11, 'name' => '墓', 'description' => '蔵に入る。貯蓄心があり、凝り性。内面を充実させる時期。'],
            ['id' => 12, 'name' => '絶', 'description' => '無の状態。束縛を嫌い、瞬間的な瞬発力を持つ。変化の多い人生。'],
        ];

        foreach ($twelveStages as $stage) {
            DB::table('master_twelve_life_stages')->updateOrInsert(['id' => $stage['id']], $stage);
        }

        // --- 3. 【新設】身強・身弱の判定基準テーブルがない場合はこちらでロジック用データを挿入 ---
        // ※今回は master_compatibility_labels を活用、または新規作成を想定
    }
}
