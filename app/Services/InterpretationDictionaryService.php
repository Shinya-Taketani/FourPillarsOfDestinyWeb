<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\MasterDataRepository;
use Illuminate\Support\Str;

readonly class InterpretationDictionaryService
{
    public function __construct(
        private MasterDataRepository $masterData,
    ) {}

    public function getTenGodText(string $name): array
    {
        return $this->entry("ten_gods.{$name}", "ten_gods.{$name}");
    }

    public function getTwelveLifeStageText(string $name): array
    {
        $description = $this->masterData->twelveLifeStagesByName()->get($name)?->description;

        if (is_string($description) && $description !== '') {
            return [
                'text' => $description,
                'summary' => $description,
                'detail' => $description,
                'source_rank' => 'PENDING',
                'source_note' => 'master_twelve_life_stages.description から取得。泰山流固有文言としての出典は要確認。',
            ];
        }

        return $this->fallback('twelve_life_stages', $name);
    }

    public function getElementText(string $name): array
    {
        return $this->entry("elements.{$name}", "elements.{$name}");
    }

    public function getCompatibilityText(string $category, string $key, array $replacements = []): array
    {
        return $this->entry("compatibility.{$category}.{$key}", "compatibility.{$category}.{$key}", $replacements);
    }

    public function getDayunTemplate(string $key): array
    {
        return $this->entry("dayun_comments.{$key}", "dayun_comments.{$key}");
    }

    public function getRyunenTemplate(string $key): array
    {
        return $this->entry("ryunen.{$key}", "ryunen.{$key}");
    }

    public function text(string $path, string $fallbackKey, array $replacements = [], string $field = 'text'): string
    {
        $entry = $this->entry($path, $fallbackKey, $replacements);

        return (string) ($entry[$field] ?? $entry['text']);
    }

    public function getFallbackText(string $category, string $key): array
    {
        return $this->fallback($category, $key);
    }

    private function entry(string $path, string $fallbackKey, array $replacements = []): array
    {
        $value = config("taizan_interpretations.{$path}");

        if ($value === null) {
            return $this->fallback($path, $fallbackKey);
        }

        $entry = is_array($value) ? $value : ['text' => $value];
        $entry = array_merge(config('taizan_interpretations.source_defaults', []), $entry);

        foreach ($entry as $key => $item) {
            if (is_string($item)) {
                $entry[$key] = $this->replace($item, $replacements);
            }
        }

        if (! isset($entry['text'])) {
            $entry['text'] = (string) ($entry['summary'] ?? $entry['detail'] ?? '');
        }

        return $entry;
    }

    private function fallback(string $category, string $key): array
    {
        $fallback = config('taizan_interpretations.fallback', [
            'text' => 'この項目の解釈文は未登録です。',
            'source_rank' => 'PENDING',
            'source_note' => '辞書未登録。泰山流固有文言は要確認。',
        ]);

        return [
            ...$fallback,
            'category' => $category,
            'key' => $key,
        ];
    }

    private function replace(string $text, array $replacements): string
    {
        foreach ($replacements as $key => $value) {
            $text = Str::replace('{{ '.$key.' }}', (string) $value, $text);
        }

        return $text;
    }
}
