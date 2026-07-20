<?php

declare(strict_types=1);

namespace App\Services;

readonly class AppraisalService
{
    public function __construct(
        private InterpretationDictionaryService $dictionary,
    ) {}

    public function generate(array $data, array $scores): array
    {
        $dayStemName = mb_substr($data['pillars']['day']['kanji'], 0, 1);
        $monthTenGod = $data['pillars']['month']['ten_god']['name'];

        return [
            'personality' => $this->text("day_stem_personality.{$dayStemName}", $dayStemName),
            'work' => $this->text("ten_god_work_style.{$monthTenGod}", $monthTenGod),
            'balance' => $this->getEnergyAdvice($scores),
            'dayun_comments' => $this->interpretDayun($data['dayun']['cycles'] ?? []),
            'saiun_comment' => $this->interpretSaiun($data['saiun']['ten_god'] ?? ''),
            'getsuun_comments' => $this->interpretGetsuun($data['getsuun'] ?? []),
            'nichiun_meanings' => $this->getNichiunMeanings(),
            'relations' => $data['relations'] ?? [],
            'judgement' => $data['judgement'] ?? null,
        ];
    }

    private function getNichiunMeanings(): array
    {
        $meanings = [];

        foreach (array_keys(config('taizan_interpretations.nichiun_meanings', [])) as $tenGod) {
            $meanings[$tenGod] = $this->text("nichiun_meanings.{$tenGod}", $tenGod);
        }

        return $meanings;
    }

    private function interpretGetsuun(array $months): array
    {
        return array_map(
            fn ($month) => ['comment' => $this->text('getsuun_comments.'.(string) ($month['ten_god'] ?? 'unknown'), (string) ($month['ten_god'] ?? 'unknown'))],
            $months,
        );
    }

    private function interpretSaiun(string $tenGod): string
    {
        return $this->text("saiun_comments.{$tenGod}", $tenGod);
    }

    private function getEnergyAdvice(array $scores): string
    {
        if (empty($scores)) {
            return $this->text('energy_advice.empty', 'energy_advice.empty');
        }

        usort($scores, fn ($a, $b) => $b['score'] <=> $a['score']);

        return $this->text('energy_advice.dominant', 'energy_advice.dominant', [
            'element' => $scores[0]['element'],
        ]);
    }

    private function interpretDayun(array $cycles): array
    {
        return array_map(
            fn ($cycle) => ['comment' => $this->text('dayun_comments.'.(string) ($cycle['ten_god'] ?? 'unknown'), (string) ($cycle['ten_god'] ?? 'unknown'))],
            $cycles,
        );
    }

    /**
     * 2人の相性を精密に鑑定する。
     * スコア算出分岐は既存ロジックを維持し、文言だけ辞書から取得する。
     */
    public function compareDestiny(array $result1, array $result2): array
    {
        $totalScore = 60;
        $details = [];

        $yearBranch1 = mb_substr($result1['pillars']['year']['kanji'], 1, 1);
        $yearBranch2 = mb_substr($result2['pillars']['year']['kanji'], 1, 1);
        $yearRelation = $this->checkYearBranchRelation($yearBranch1, $yearBranch2);
        $totalScore += $yearRelation['score'];
        $details['year_relation'] = $yearRelation;

        $stem1 = mb_substr($result1['pillars']['day']['kanji'], 0, 1);
        $stem2 = mb_substr($result2['pillars']['day']['kanji'], 0, 1);
        $stemRelation = $this->checkStemRelation($stem1, $stem2);
        $totalScore += $stemRelation['score'];
        $details['stem_relation'] = $stemRelation;

        $gogyoFlow = $this->calculateDetailedGogyoFlow(
            $result1['five_elements_scores'],
            $result2['five_elements_scores'],
        );
        $totalScore += $gogyoFlow['score'];
        $details['balance_relation'] = $gogyoFlow;

        $finalScore = max(0, min(100, $totalScore));

        return [
            'total_score' => $finalScore,
            'details' => $details,
            'summary' => $this->generateCompatibilitySummary($finalScore),
        ];
    }

    private function calculateDetailedGogyoFlow(array $scores1, array $scores2): array
    {
        $getStrongest = function ($scores) {
            usort($scores, fn ($a, $b) => $b['score'] <=> $a['score']);

            return $scores[0]['element'];
        };

        $strong1 = $getStrongest($scores1);
        $strong2 = $getStrongest($scores2);

        $sosho = ['木' => '火', '火' => '土', '土' => '金', '金' => '水', '水' => '木'];
        $sokoku = ['木' => '土', '土' => '水', '水' => '火', '火' => '金', '金' => '木'];

        if ($strong1 === $strong2) {
            return $this->compatibilityResult('gogyo', 'hiwa', 10, [
                'self' => $strong1,
                'other' => $strong2,
            ]);
        }

        if ($sosho[$strong1] === $strong2) {
            return $this->compatibilityResult('gogyo', 'sosho_supporting', 20, [
                'self' => $strong1,
                'other' => $strong2,
            ]);
        }

        if ($sosho[$strong2] === $strong1) {
            return $this->compatibilityResult('gogyo', 'sosho_supported', 20, [
                'self' => $strong1,
                'other' => $strong2,
            ]);
        }

        if ($sokoku[$strong1] === $strong2) {
            return $this->compatibilityResult('gogyo', 'sokoku_controlling', -5, [
                'self' => $strong1,
                'other' => $strong2,
            ]);
        }

        if ($sokoku[$strong2] === $strong1) {
            return $this->compatibilityResult('gogyo', 'sokoku_controlled', -5, [
                'self' => $strong1,
                'other' => $strong2,
            ]);
        }

        return $this->compatibilityResult('gogyo', 'neutral', 5);
    }

    private function checkYearBranchRelation(string $b1, string $b2): array
    {
        $sango = ['水局' => ['申', '子', '辰'], '木局' => ['亥', '卯', '未'], '火局' => ['寅', '午', '戌'], '金局' => ['巳', '酉', '丑']];

        foreach ($sango as $element => $group) {
            if (in_array($b1, $group, true) && in_array($b2, $group, true) && $b1 !== $b2) {
                return $this->compatibilityResult('year_branch', 'sango', 20, ['type' => $element]);
            }
        }

        $sochu = ['子' => '午', '午' => '子', '卯' => '酉', '酉' => '卯', '寅' => '申', '申' => '寅', '巳' => '亥', '亥' => '巳', '辰' => '戌', '戌' => '辰', '丑' => '未', '未' => '丑'];

        if (isset($sochu[$b1]) && $sochu[$b1] === $b2) {
            return $this->compatibilityResult('year_branch', 'sochu', -15);
        }

        $sankei = ['無恩の刑' => ['寅', '巳', '申'], '持勢の刑' => ['丑', '戌', '未'], '無礼の刑' => ['子', '卯']];

        foreach ($sankei as $type => $group) {
            if (in_array($b1, $group, true) && in_array($b2, $group, true) && $b1 !== $b2) {
                return $this->compatibilityResult('year_branch', 'sankei', -10, ['type' => $type]);
            }
        }

        return $this->compatibilityResult('year_branch', 'neutral', 0);
    }

    private function checkStemRelation(string $s1, string $s2): array
    {
        $kango = ['甲' => '己', '己' => '甲', '乙' => '庚', '庚' => '乙', '丙' => '辛', '辛' => '丙', '丁' => '壬', '壬' => '丁', '戊' => '癸', '癸' => '戊'];

        if (isset($kango[$s1]) && $kango[$s1] === $s2) {
            return $this->compatibilityResult('stem', 'kango', 20);
        }

        $sokoku = ['甲' => '戊', '戊' => '壬', '壬' => '丙', '丙' => '庚', '庚' => '甲', '乙' => '己', '己' => '癸', '癸' => '丁', '丁' => '辛', '辛' => '乙'];

        if ((isset($sokoku[$s1]) && $sokoku[$s1] === $s2) || (isset($sokoku[$s2]) && $sokoku[$s2] === $s1)) {
            return $this->compatibilityResult('stem', 'sokoku', -10);
        }

        return $this->compatibilityResult('stem', 'neutral', 0);
    }

    private function generateCompatibilitySummary(int $score): string
    {
        if ($score >= 80) {
            return $this->text('compatibility.summary.excellent', 'compatibility.summary.excellent');
        }

        if ($score >= 50) {
            return $this->text('compatibility.summary.good', 'compatibility.summary.good');
        }

        return $this->text('compatibility.summary.challenging', 'compatibility.summary.challenging');
    }

    private function compatibilityResult(string $category, string $key, int $score, array $replacements = []): array
    {
        $entry = $this->dictionary->getCompatibilityText($category, $key, $replacements);

        return [
            'score' => $score,
            'name' => (string) ($entry['name'] ?? $entry['text']),
            'conclusion' => (string) ($entry['conclusion'] ?? $entry['text']),
            'advice' => (string) ($entry['advice'] ?? $entry['text']),
        ];
    }

    private function text(string $path, string $fallbackKey, array $replacements = []): string
    {
        return $this->dictionary->text($path, $fallbackKey, $replacements);
    }
}
