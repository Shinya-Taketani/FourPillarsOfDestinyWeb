<?php

declare(strict_types=1);

namespace App\Services;

readonly class AppraisalService
{
    public function generate(array $data, array $scores): array
    {
        $dayStemName = mb_substr($data['pillars']['day']['kanji'], 0, 1);
        $monthTenGod = $data['pillars']['month']['ten_god']['name'];
        
        return [
            'personality' => $this->getPersonality($dayStemName),
            'work' => $this->getWorkStyle($monthTenGod),
            'balance' => $this->getEnergyAdvice($scores),
            'dayun_comments' => $this->interpretDayun($data['dayun']['cycles'] ?? []),
            'saiun_comment' => $this->interpretSaiun($data['saiun']['ten_god'] ?? ''),
            'getsuun_comments' => $this->interpretGetsuun($data['getsuun'] ?? []),
            'nichiun_meanings' => $this->getNichiunMeanings(),
        ];
    }

    private function getNichiunMeanings(): array
    {
        return [
            '比肩'=>'意志を貫く。','劫財'=>'協力か出費。','食神'=>'幸運を楽しむ。','傷官'=>'感性冴える。','偏財'=>'チャンス。',
            '正財'=>'誠実・着実。','偏官'=>'変化と多忙。','正官'=>'信用と名誉。','偏印'=>'知的好奇心。','印綬'=>'学びと癒やし。'
        ];
    }

    private function interpretGetsuun(array $months): array
    {
        $meanings = [
            '比肩' => '自分を強く持つ月。意思が通りやすい反面、強引さには注意が必要です。',
            '劫財' => '社交が活発で、出費が増えがちな月。人との協力が成功の鍵となります。',
            '食神' => '心身ともにリラックスできる幸運月。楽しみや食、趣味を優先して吉。',
            '傷官' => '感性が鋭くなり、仕事や表現で成果が出る月。言葉のトゲには気を付けて。',
            '偏財' => '人やお金の動きが激しくなるチャンス月。積極的に動くことで実を結びます。',
            '正財' => '堅実な努力が認められる月。家計や仕事の土台を整えるのに最適な時期。',
            '偏官' => '非常に多忙で、責任ある立場に置かれる月。スピード感を持って対処を。',
            '正官' => '信用が高まり、物事が計画通りに進む安定月。正攻法でのアプローチが吉。',
            '偏印' => '新しいアイデアや知識を吸収できる月。副業や新しい趣味に縁があります。',
            '印綬' => '周囲の援助を得て、物事が静かに解決に向かう月。学びや研究に集中できます。',
        ];
        return array_map(fn($m) => ['comment' => $meanings[$m['ten_god']] ?? '今月のリズムを大切に。'], $months);
    }

    private function interpretSaiun(string $tenGod): string
    {
        $meanings = [
            '比肩' => '自分自身が主役となる一年。新しい決断や独立には最適ですが、周囲への配慮も忘れずに。',
            '劫財' => 'エネルギーに溢れ、人との交流が激しくなる年。大きな投資や衝動買いには注意が必要です。',
            '食神' => '楽しさと豊かさに恵まれる一年。趣味や美食、恋愛など、人生を謳歌することで運気が開けます。',
            '傷官' => '感受性が鋭くなり、クリエイティブな活動に没頭できる年。言葉のトゲに注意し、静かに技術を磨くのが吉。',
            '偏財' => '人脈が広がり、金運も活発に動く「勝負」の年。多忙を極めますが、積極的に動くほど成果が出ます。',
            '正財' => '堅実な歩みが実を結ぶ安定した一年。結婚や貯蓄、マイホーム計画など、基盤を固めるのに最適です。',
            '偏官' => '変化と挑戦の年。多忙で責任も重くなりますが、それを乗り越えることで一皮むけるチャンスです。',
            '正官' => '名誉と信頼を得る最高の一年。これまでの努力が認められ、社会的な地位や評価が向上します。',
            '偏印' => '知的好奇心が刺激される年。習い事や旅行、副業など、新しい世界に飛び込むことで発見があります。',
            '印綬' => '学びと癒やしの一年。周囲からの援助を受けやすく、精神的に落ち着いた豊かな時間を過ごせます。',
        ];
        return $meanings[$tenGod] ?? '2026年を活かして前進できる一年です。';
    }

    private function getPersonality(string $dayStem): string
    {
        $texts = [
            '甲' => '「大樹」の人。真っ直ぐで正義感が強く、リーダー気質ですが、頑固で折れやすい一面もあります。',
            '乙' => '「草花」の人。柔軟で協調性があり、一見弱々しく見えても踏まれても立ち上がる粘り強さを持っています。',
            '丙' => '「太陽」の人。明るく社交的で、周囲を照らすカリスマ性がありますが、感情の起伏が激しくなりがち。',
            '丁' => '「灯火」の人。内面に情熱を秘めた慎重派。独自の感性を持ち、特定の分野で鋭い才能を発揮します。',
            '戊' => '「山」の人。どっしりとした存在感と包容力があります。保守的ですが、信頼感は抜群です。',
            '己' => '「大地」の人。多才で教養があり、人を育てるのが得意。内面に複雑な感情を抱えやすいタイプです。',
            '庚' => '「鉄」の人。意志が強く決断力に優れます。自分を磨くことで光りますが、攻撃的にならないよう注意。',
            '辛' => '「宝石」の人。繊細で美意識が高く、プライドも高め。試練を経験するほど輝きが増す命運です。',
            '壬' => '「海」の人。自由奔放で知性的。大きな流動性を持ち、スケールの大きな仕事を成し遂げる器があります。',
            '癸' => '「雨」の人。純粋で奉仕精神が旺盛。静かに周囲を潤しますが、考え込みすぎてしまうことも。',
        ];
        return $texts[$dayStem] ?? '独自の個性を秘めています。';
    }

    private function getWorkStyle(string $tenGod): string
    {
        $texts = [
            '比肩' => '独立独歩。自分のペースで進められる専門職や、自分の名前で勝負する仕事が向いています。',
            '劫財' => '野心家で社交的。組織を動かす力がありますが、金銭管理や協力者との調和が成功の鍵です。',
            '食神' => 'クリエイティブ・表現。楽しみながら働くことで運気が上がります。食・芸術・技術系に適性。',
            '傷官' => '鋭い感受性と分析力。技術職、デザイン、あるいは専門知識を活かしたコンサル業務に強み。',
            '偏財' => '流動資産の才。営業、商売、投資など、人脈とスピードを活かした華やかな分野で活躍します。',
            '正財' => '着実・誠実。事務や管理、銀行業務など、正確さと信頼を求められる仕事で実力を発揮します。',
            '偏官' => '行動派・親分肌。スピード感が求められる現場や、開拓精神が必要なベンチャー、警察・自衛等に適性。',
            '正官' => '規律・責任。公務員や大企業、管理職。社会的な責任を果たすことにやりがいを感じるタイプです。',
            '偏印' => '特殊技能・発想。伝統に縛られない自由なスタイル。IT、企画、趣味を仕事にする道が吉。',
            '印綬' => '教育・学問。知識を吸収し、それを伝える仕事。講師、研究職、安定した伝統ある組織に向きます。',
        ];
        return $texts[$tenGod] ?? '現在の役割において、あなたの持ち味が活かされています。';
    }

    private function getEnergyAdvice(array $scores): string
    {
        if (empty($scores)) return 'バランスを大切に。';
        usort($scores, fn($a, $b) => $b['score'] <=> $a['score']);
        return "エネルギーバランスを見ると、現在「{$scores[0]['element']}」の力が最も強く、バランスが偏りやすい時期。意識して調整することで運気が安定します。";
    }

    private function interpretDayun(array $cycles): array
    {
        $meanings = [
            '比肩' => '自分自身を見つめ直す時期。独立心が強まり、新しいことに挑戦したくなりますが、独りよがりにならないよう注意。',
            '劫財' => 'エネルギーが外に向かう時期。仲間との協力や社交が活発になりますが、出費やトラブルにも気を配って。',
            '食神' => '衣食住が充実し、心身ともにゆとりが出る時期。クリエイティブな才能が開花し、人生を楽しむ余裕が生まれます。',
            '傷官' => '感受性が鋭くなり、技術や芸術面で飛躍できる時期。一方で、言葉による衝突や孤独感を感じやすい傾向も。',
            '偏財' => '人脈が広がり、金運・商売運が活発になる時期。多忙になりますが、チャンスを掴みやすい「動」の10年です。',
            '正財' => '着実な積み重ねが実を結ぶ安定期。家庭運や蓄財運に恵まれ、信頼を築くることで長期的な基盤が整います。',
            '偏官' => '変化と激動の時期。責任ある立場を任されたり、環境が大きく変わったりします。勇気を持って立ち向かうことで成長します。',
            '正官' => '名誉や社会的信用を得る時期。規律正しく行動することで、社会からの評価が高まり、安定した地位を築けます。',
            '偏印' => '好奇心や探求心が強まる時期。伝統に縛られない自由な発想で、副業や趣味、特殊な分野で道が開けます。',
            '印綬' => '学びと精神的充足の時期。知識の習得や教育的な活動に縁があり、目上の人からの引き立ても期待できる安定した運気です。',
        ];
        return array_map(fn($c) => ['comment' => $meanings[$c['ten_god']] ?? '着実に進むべき時期です。'], $cycles);
    }

    /**
     * 2人の相性を精密に鑑定する
     */
    public function compareDestiny(array $result1, array $result2): array
    {
        $totalScore = 60;
        $details = [];

        // 地支を抽出
        $yearBranch1 = mb_substr($result1['pillars']['year']['kanji'], 1, 1);
        $yearBranch2 = mb_substr($result2['pillars']['year']['kanji'], 1, 1);

        // 年柱の相性判定
        $yearRelation = $this->checkYearBranchRelation($yearBranch1, $yearBranch2);
        $totalScore += $yearRelation['score'];
        $details['year_relation'] = $yearRelation;

        // 日干を抽出
        $stem1 = mb_substr($result1['pillars']['day']['kanji'], 0, 1);
        $stem2 = mb_substr($result2['pillars']['day']['kanji'], 0, 1);
        $stemRelation = $this->checkStemRelation($stem1, $stem2);
        $totalScore += $stemRelation['score'];
        $details['stem_relation'] = $stemRelation;

        // 五行補完（ここも結論＋助言に統一）
        $balanceRelation = [
            'score' => 5, 
            'name' => '五行補完', 
            'conclusion' => 'お互いのエネルギーを補い合える、バランスの良い関係です。',
            'advice' => '二人でいることで精神的な安定が得られます。無理に合わせようとせず自然体で過ごしてください。'
        ];
        $totalScore += $balanceRelation['score'];
        $details['balance_relation'] = $balanceRelation;

        $finalScore = max(0, min(100, $totalScore));

        return [
            'total_score' => $finalScore,
            'details' => $details,
            'summary' => $this->generateCompatibilitySummary($finalScore)
        ];
    }

    /**
     * 年柱の判定ロジック
     */
    private function checkYearBranchRelation(string $b1, string $b2): array
    {
        $sango = ['水局'=>['申','子','辰'], '木局'=>['亥','卯','未'], '火局'=>['寅','午','戌'], '金局'=>['巳','酉','丑']];
        foreach ($sango as $element => $group) {
            if (in_array($b1, $group) && in_array($b2, $group) && $b1 !== $b2) {
                $advice = [
                    '水局' => '知略と適応力で困難を回避できます。二人で計画を練る時間を大切にしてください。',
                    '木局' => '共存共栄の精神が成功の鍵です。互いの成長を喜び合える環境を整えましょう。',
                    '火局' => '爆発的な推進力がありますが、独走に注意。周囲を置き去りにしない配慮が必要です。',
                    '金局' => '強固な信頼を築けます。約束事を重んじ、長期的な資産形成を共に歩むのが吉です。',
                ][$element];
                return [
                    'score' => 20, 
                    'name' => "三合会局（{$element}）", 
                    'conclusion' => "個人の運命を超えた巨大な潮流を生み出す、宿命的な結束です。", 
                    'advice' => $advice
                ];
            }
        }

        $sochu = ['子'=>'午','午'=>'子','卯'=>'酉','酉'=>'卯','寅'=>'申','申'=>'寅','巳'=>'亥','亥'=>'巳','辰'=>'戌','戌'=>'辰','丑'=>'未','未'=>'丑'];
        if (isset($sochu[$b1]) && $sochu[$b1] === $b2) {
            $isOu = in_array($b1, ['子', '午', '卯', '酉']);
            $advice = $isOu ? "感情が激突しやすいため、一時の感情で言葉を投げない「沈黙の知恵」を持ってください。" : "役割分担を明確にし、互いの領域を侵さないことが共存の秘訣です。";
            return [
                'score' => -15, 
                'name' => '地支相沖', 
                'conclusion' => "魂を研磨し合う「激しい摩擦」の関係です。", 
                'advice' => $advice
            ];
        }

        $sankei = ['無恩の刑'=>['寅','巳','申'], '持勢の刑'=>['丑','戌','未'], '無礼の刑'=>['子','卯']];
        foreach ($sankei as $type => $group) {
            if (in_array($b1, $group) && in_array($b2, $group) && $b1 !== $b2) {
                $advice = [
                    '無恩の刑' => '甘えが不信感に変わる恐れがあります。親しい仲にも峻烈な礼儀を忘れないでください。', 
                    '持勢の刑' => '意地の張り合いがトラブルの元です。自尊心よりも実利を優先する柔軟さを持ちましょう。', 
                    '無礼の刑' => 'デリカシーを欠いた言動に注意が必要です。相手の聖域に踏み込まない節度が鍵となります。'
                ][$type];
                return [
                    'score' => -10, 
                    'name' => "三刑殺（{$type}）", 
                    'conclusion' => "心理的な摩擦が生じやすい関係です。", 
                    'advice' => $advice
                ];
            }
        }
        return [
            'score' => 0, 
            'name' => '平穏', 
            'conclusion' => '家系的な衝突はなく、自然体で付き合える安定した関係です。', 
            'advice' => '特別な対策は不要です。お互いの個性をそのまま尊重し合ってください。'
        ];
    }

    private function checkStemRelation($s1, $s2): array {
        $kango = ['甲'=>'己','己'=>'甲','乙'=>'庚','庚'=>'乙','丙'=>'辛','辛'=>'丙','丁'=>'壬','壬'=>'丁','戊'=>'癸','癸'=>'戊'];
        if (isset($kango[$s1]) && $kango[$s1] === $s2) {
            return [
                'score' => 20, 
                'name' => '干合', 
                'conclusion' => '精神的に強く惹かれ合う、最高の結びつきです。', 
                'advice' => '理屈抜きに一体感を感じられます。その絆を過信せず、言葉での感謝を忘れずに。'
            ];
        }
        return [
            'score' => 0, 
            'name' => '中庸', 
            'conclusion' => '精神面での大きな波風はありません。', 
            'advice' => '波長が穏やかなため、時間をかけて信頼を深めていくことができます。'
        ];
    }

    private function generateCompatibilitySummary($score): string {
        if ($score >= 80) return "宿命的な縁で結ばれた最高のパートナーです。";
        if ($score >= 50) return "安定した関係を築ける良好な相性です。";
        return "互いの違いを尊重することで道が開ける関係です。";
    }
}
