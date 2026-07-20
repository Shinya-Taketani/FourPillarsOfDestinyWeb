<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\SolarTermCatalog;
use DateTimeImmutable;
use DOMDocument;
use DOMXPath;
use RuntimeException;

final class NaojSolarTermPageParser
{
    /**
     * @return list<array{year:int,term_name:string,longitude_degree:int,started_at:string}>
     */
    public function parse(string $html, int $year): array
    {
        $utf8Html = $this->toUtf8($html);
        $document = new DOMDocument;
        $previousErrors = libxml_use_internal_errors(true);
        $loaded = $document->loadHTML('<?xml encoding="UTF-8">'.$utf8Html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previousErrors);

        if (! $loaded) {
            throw new RuntimeException("{$year}年の国立天文台HTMLを解析できませんでした。");
        }

        $xpath = new DOMXPath($document);
        $pageText = $this->normalizeText($document->textContent);

        if (! str_contains($pageText, $year.'年の天象') || ! str_contains($pageText, '標準時:UT+9h')) {
            throw new RuntimeException("{$year}年のページまたは中央標準時であることを確認できませんでした。");
        }

        $expectedTerms = SolarTermCatalog::indexedByName();
        $events = $this->emptyEvents();

        foreach ($xpath->query('//table[@id="phenom"]/tr') ?: [] as $row) {
            $cells = [];

            foreach ($xpath->query('./td', $row) ?: [] as $cell) {
                $cells[] = $this->normalizeText($cell->textContent);
            }

            if (count($cells) !== 6 || $cells[3] !== '二十四節気') {
                continue;
            }

            if (preg_match('/^(?<name>.+)\(黄経(?<degree>\d+)°\)$/u', $cells[5], $termMatches) !== 1) {
                throw new RuntimeException("{$year}年の二十四節気名または黄経を解析できません: {$cells[5]}");
            }

            $name = $termMatches['name'];
            $degree = (int) $termMatches['degree'];
            $expected = $expectedTerms[$name] ?? null;

            if ($expected === null || $expected['longitude_degree'] !== $degree) {
                throw new RuntimeException("{$year}年の節気名と黄経が定義に一致しません: {$name} {$degree}度");
            }

            if (in_array($name, array_column($events, 'term_name'), true)) {
                throw new RuntimeException("{$year}年の{$name}が重複しています。");
            }

            if (preg_match('/^(\d{4})\/(\d{2})\/(\d{2})$/', $cells[0], $dateMatches) !== 1
                || preg_match('/^(\d{2}):(\d{2})$/', $cells[1], $timeMatches) !== 1) {
                throw new RuntimeException("{$year}年の{$name}の日時を分単位で解析できません。");
            }

            if ((int) $dateMatches[1] !== $year) {
                throw new RuntimeException("{$year}年のページに別年の{$name}が含まれています。");
            }

            $hour = (int) $timeMatches[1];
            $minute = (int) $timeMatches[2];
            $date = sprintf('%04d-%02d-%02d', $year, (int) $dateMatches[2], (int) $dateMatches[3]);

            if ($hour === 24 && $minute === 0) {
                $dateTime = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $date.' 00:00');
                if ($dateTime !== false) {
                    $dateTime = $dateTime->modify('+1 day');
                }
            } elseif ($hour >= 0 && $hour <= 23 && $minute >= 0 && $minute <= 59) {
                $dateTime = DateTimeImmutable::createFromFormat('!Y-m-d H:i', sprintf('%s %02d:%02d', $date, $hour, $minute));
            } else {
                $dateTime = false;
            }

            $dateErrors = DateTimeImmutable::getLastErrors();

            if ($dateTime === false || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))) {
                throw new RuntimeException("{$year}年の{$name}の日時が不正です。");
            }

            $events[] = [
                'year' => $year,
                'term_name' => $name,
                'longitude_degree' => $degree,
                'started_at' => $dateTime->format('Y-m-d H:i:00'),
            ];
        }

        $missing = array_values(array_diff(array_keys($expectedTerms), array_column($events, 'term_name')));

        if (count($events) !== 24 || $missing !== []) {
            throw new RuntimeException(sprintf(
                '%d年の二十四節気が24件揃っていません（取得%d件、欠損: %s）。',
                $year,
                count($events),
                $missing === [] ? 'なし' : implode('、', $missing),
            ));
        }

        for ($index = 1; $index < count($events); $index++) {
            if ($events[$index]['started_at'] <= $events[$index - 1]['started_at']) {
                throw new RuntimeException("{$year}年の二十四節気が時系列順ではありません。");
            }
        }

        return $events;
    }

    /**
     * @return list<array{year:int,term_name:string,longitude_degree:int,started_at:string}>
     */
    private function emptyEvents(): array
    {
        return [];
    }

    private function toUtf8(string $html): string
    {
        $encoding = mb_detect_encoding($html, ['UTF-8', 'EUC-JP'], true);

        if ($encoding === false) {
            throw new RuntimeException('国立天文台HTMLの文字コードを判定できませんでした。');
        }

        $utf8Html = $encoding === 'UTF-8' ? $html : mb_convert_encoding($html, 'UTF-8', $encoding);
        $normalizedHtml = preg_replace(
            '/charset\s*=\s*(["\']?)EUC-JP\1/i',
            'charset=UTF-8',
            $utf8Html,
        );

        if ($normalizedHtml === null) {
            throw new RuntimeException('国立天文台HTMLの文字コード宣言を正規化できませんでした。');
        }

        return $normalizedHtml;
    }

    private function normalizeText(string $value): string
    {
        $decoded = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return preg_replace('/\s+/u', '', $decoded) ?? '';
    }
}
