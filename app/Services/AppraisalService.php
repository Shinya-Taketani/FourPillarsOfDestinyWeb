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
            '偏財' => '人脈が広がり、金運 Yealy も活発に動く「勝負」の年。多忙を極めますが、積極的に動くほど成果が出ます。',
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
            '正財' => '着実な積み重ねが実を結ぶ安定期。家庭運や蓄財運に恵まれ、信頼を築くことで長期的な基盤が整います。',
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

        // 1. 年柱（ルーツ）の相性判定
        $yearBranch1 = mb_substr($result1['pillars']['year']['kanji'], 1, 1);
        $yearBranch2 = mb_substr($result2['pillars']['year']['kanji'], 1, 1);
        $yearRelation = $this->checkYearBranchRelation($yearBranch1, $yearBranch2);
        $totalScore += $yearRelation['score'];
        $details['year_relation'] = $yearRelation;

        // 2. 日干（精神）の相性判定
        $stem1 = mb_substr($result1['pillars']['day']['kanji'], 0, 1);
        $stem2 = mb_substr($result2['pillars']['day']['kanji'], 0, 1);
        $stemRelation = $this->checkStemRelation($stem1, $stem2);
        $totalScore += $stemRelation['score'];
        $details['stem_relation'] = $stemRelation;

        // 3. 五行力量の相生・比和・相克判定（強化版）
        $gogyoFlow = $this->calculateDetailedGogyoFlow(
            $result1['five_elements_scores'], 
            $result2['five_elements_scores']
        );
        $totalScore += $gogyoFlow['score'];
        $details['balance_relation'] = $gogyoFlow;

        $finalScore = max(0, min(100, $totalScore));

        return [
            'total_score' => $finalScore,
            'details' => $details,
            'summary' => $this->generateCompatibilitySummary($finalScore)
        ];
    }

    /**
     * 五行の相生・比和・相克を詳細に判定する
     */
    private function calculateDetailedGogyoFlow(array $scores1, array $scores2): array
    {
        // それぞれの最強五行を特定
        $getStrongest = function($scores) {
            usort($scores, fn($a, $b) => $b['score'] <=> $a['score']);
            return $scores[0]['element'];
        };

        $strong1 = $getStrongest($scores1);
        $strong2 = $getStrongest($scores2);

        // 相生マップ (自分 -> 相手)
        $sosho = ['木'=>'火', '火'=>'土', '土'=>'金', '金'=>'水', '水'=>'木'];
        // 相克マップ (自分 -> 相手)
        $sokoku = ['木'=>'土', '土'=>'水', '水'=>'火', '火'=>'金', '金'=>'木'];

        // 1. 比和（同じ五行）
        if ($strong1 === $strong2) {
            return [
                'score' => 10,
                'name' => "五行比和（{$strong1}と{$strong1}）",
                'conclusion' => "お互いに「{$strong1}」の強い気を持つ、魂の波長が近い共鳴の関係です。",
                'advice' => "似た者同士ゆえ、阿吽の呼吸で理解し合えますが、一度ぶつかると譲らなくなる傾向も。共通の目的を持つことで、二人の力は何倍にも膨らみます。"
            ];
        }

        // 2. 相生（生み出す・生み出される）
        if ($sosho[$strong1] === $strong2) {
            return [
                'score' => 20,
                'name' => "五行相生（{$strong1}生{$strong2}）",
                'conclusion' => "あなたのエネルギーが自然と相手を活かし、幸運を育む「献身」の相性です。",
                'advice' => "あなたが相手をサポートすることで、相手の才能が大きく開花します。相手の成長を自分の喜びとすることで、二人の運気は無限に循環します。"
            ];
        }
        if ($sosho[$strong2] === $strong1) {
            return [
                'score' => 20,
                'name' => "五行相生（{$strong2}生{$strong1}）",
                'conclusion' => "相手のエネルギーがあなたに活力を与え、心身を癒やしてくれる「慈愛」の関係です。",
                'advice' => "相手と一緒にいるだけで、あなたは不思議と元気になれるはず。相手の優しさに素直に甘え、感謝を言葉にすることが絆を深める鍵です。"
            ];
        }

        // 3. 相克（抑える・抑えられる）
        if ($sokoku[$strong1] === $strong2) {
            return [
                'score' => -5,
                'name' => "五行相克（{$strong1}剋{$strong2}）",
                'conclusion' => "あなたの強い個性が、無意識のうちに相手を圧倒してしまう緊張感のある相性です。",
                'advice' => "あなたが主導権を握りやすい関係ですが、相手の主体性を重んじることが大切です。一歩引いて見守る「寛容さ」が、摩擦を最高の刺激に変えます。"
            ];
        }
        if ($sokoku[$strong2] === $strong1) {
            return [
                'score' => -5,
                'name' => "五行相克（{$strong2}剋{$strong1}）",
                'conclusion' => "相手の存在があなたにプレッシャーを与え、自分らしさを出しにくいと感じる「修行」の相です。",
                'advice' => "相手の厳しさは、あなたを磨くための「砥石」のようなもの。反発するのではなく、自分を成長させるためのアドバイスと捉えることで、道が開けます。"
            ];
        }

        return [
            'score' => 5,
            'name' => '五行中庸',
            'conclusion' => "互いのエネルギーが衝突せず、穏やかに調和している安定した関係です。",
            'advice' => "派手な刺激はありませんが、一緒にいて最も疲れにくい相性です。日常生活の何気ない会話を大切にすることで、生涯の伴侶となれるでしょう。"
        ];
    }

    /**
     * 年柱（年支）の特殊関係を判定
     */
    private function checkYearBranchRelation(string $b1, string $b2): array
    {
        $sango = ['水局'=>['申','子','辰'], '木局'=>['亥','卯','未'], '火局'=>['寅','午','戌'], '金局'=>['巳','酉','丑']];
        foreach ($sango as $element => $group) {
            if (in_array($b1, $group) && in_array($b2, $group) && $b1 !== $b2) {
                return [
                    'score' => 20, 
                    'name' => "三合会局（{$element}）", 
                    'conclusion' => "個人の運命を超えた巨大な潮流を生み出す、宿命的な結束です。", 
                    'advice' => "二人が揃うことで、不可能を可能にする推進力が生まれます。共通の大きな目標を持つことで、運気はさらに加速します。"
                ];
            }
        }

        $sochu = ['子'=>'午','午'=>'子','卯'=>'酉','酉'=>'卯','寅'=>'申','申'=>'寅','巳'=>'亥','亥'=>'巳','辰'=>'戌','戌'=>'辰','丑'=>'未','未'=>'丑'];
        if (isset($sochu[$b1]) && $sochu[$b1] === $b2) {
            return [
                'score' => -15, 
                'name' => '地支相沖', 
                'conclusion' => "魂を研磨し合う「激しい摩擦」の関係です。", 
                'advice' => "価値観が正面から衝突するため、適度な距離感が不可欠です。違いを否定せず、互いの領域を尊重する「大人の対応」が共存の鍵となります。"
            ];
        }

        $sankei = ['無恩の刑'=>['寅','巳','申'], '持勢の刑'=>['丑','戌','未'], '無礼の刑'=>['子','卯']];
        foreach ($sankei as $type => $group) {
            if (in_array($b1, $group) && in_array($b2, $group) && $b1 !== $b2) {
                return [
                    'score' => -10, 
                    'name' => "三刑殺（{$type}）", 
                    'conclusion' => "無意識のうちに相手の自尊心を削り合う、心理的な摩擦が生じやすい関係です。", 
                    'advice' => "「親しき仲にも礼儀あり」を徹底してください。甘えを捨てて誠実に向き合うことで、不要な衝突を避けることができます。"
                ];
            }
        }

        return [
            'score' => 0, 
            'name' => '平穏', 
            'conclusion' => '家系的な衝突はなく、自然体で付き合える安定した関係です。', 
            'advice' => '特別な対策は不要です。お互いの個性をそのまま尊重し、穏やかな時間を育んでください。'
        ];
    }

    /**
     * 日干（精神面）の相性を詳細に判定
     */
    private function checkStemRelation($s1, $s2): array {
        $kango = ['甲'=>'己','己'=>'甲','乙'=>'庚','庚'=>'乙','丙'=>'辛','辛'=>'丙','丁'=>'壬','壬'=>'丁','戊'=>'癸','癸'=>'戊'];
        if (isset($kango[$s1]) && $kango[$s1] === $s2) {
            return [
                'score' => 20, 'name' => '干合', 
                'conclusion' => '精神的に強く惹かれ合い、一体化するような最高の結びつきです。',
                'advice' => '理屈抜きに相手を理解でき、深い充足感を得られます。ただし、依存しすぎないよう自分自身の軸を保つことも忘れないでください。'
            ];
        }

        $soukoku = ['甲'=>'戊','戊'=>'壬','壬'=>'丙','丙'=>'庚','庚'=>'甲','乙'=>'己','己'=>'癸','癸'=>'丁','丁'=>'辛','辛'=>'乙'];
        if (isset($soukoku[$s1]) && $soukoku[$s1] === $s2 || isset($soukoku[$s2]) && $soukoku[$s2] === $s1) {
            return [
                'score' => -10, 'name' => '精神的摩擦', 
                'conclusion' => '価値観が真っ向から対立しやすく、緊張感が生じやすい関係です。',
                'advice' => '相手を「変えよう」とすると激しく衝突します。「自分とは違う宇宙の人」と認め、その違いを面白がる寛容さが平和への近道です。'
            ];
        }

        return [
            'score' => 0, 'name' => '中庸', 
            'conclusion' => '精神面での大きな波風はなく、フラットな関係性を維持できます。', 
            'advice' => '刺激は少ないですが、長続きする相性です。お互いの礼儀を忘れなければ、生涯の友や信頼できるパートナーになれるでしょう。'
        ];
    }

    private function generateCompatibilitySummary($score): string {
        if ($score >= 80) return "宿命的な縁で結ばれた最高のパートナーです。";
        if ($score >= 50) return "安定した関係を築ける良好な相性です。";
        return "互いの違いを尊重することで道が開ける関係です。";
    }
}
