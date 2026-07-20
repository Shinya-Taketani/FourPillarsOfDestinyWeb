<?php

declare(strict_types=1);

namespace App\Services\Calendar;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use JsonException;
use RuntimeException;
use Throwable;

final class NaojSolarTermClient
{
    public const BASE_URL = 'https://eco.mtk.nao.ac.jp/cgi-bin/koyomi/cande/phenomena_sy.cgi';

    /**
     * @return array{body:string,source_url:string,source_accessed_on:string,raw_content_hash:string,from_cache:bool}
     */
    public function fetch(int $year, string $rawDirectory, bool $refresh = false): array
    {
        $rawPath = $rawDirectory.DIRECTORY_SEPARATOR.$year.'.html';
        $metadataPath = $rawDirectory.DIRECTORY_SEPARATOR.$year.'.json';

        if (! $refresh && is_file($rawPath) && is_file($metadataPath)) {
            return $this->readCached($rawPath, $metadataPath, $year);
        }

        $response = Http::accept('text/html')
            ->withUserAgent('FourPillarsOfDestinyWeb solar-term dataset generator')
            ->withBody(self::formBody($year), 'application/x-www-form-urlencoded')
            ->connectTimeout(10)
            ->timeout(30)
            ->retry(
                [500, 1000, 2000],
                0,
                static function (Throwable $exception): bool {
                    if ($exception instanceof ConnectionException) {
                        return true;
                    }

                    return $exception instanceof RequestException
                        && ($exception->response->status() === 429 || $exception->response->serverError());
                },
                throw: false,
            )
            ->post(self::BASE_URL);

        $response->throw();
        $body = $response->body();

        if ($body === '') {
            throw new RuntimeException("{$year}年の国立天文台HTMLが空です。");
        }

        $accessedOn = now('Asia/Tokyo')->toDateString();
        $hash = hash('sha256', $body);
        File::ensureDirectoryExists($rawDirectory);
        $this->writeAtomically($rawPath, $body);
        $this->writeAtomically($metadataPath, json_encode([
            'year' => $year,
            'source_url' => self::citationUrl($year),
            'source_accessed_on' => $accessedOn,
            'raw_content_hash' => $hash,
        ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);

        return [
            'body' => $body,
            'source_url' => self::citationUrl($year),
            'source_accessed_on' => $accessedOn,
            'raw_content_hash' => $hash,
            'from_cache' => false,
        ];
    }

    public static function formBody(int $year): string
    {
        $parameters = [
            'year' => $year,
            'lst' => 9,
            'lsti' => 9,
            'phenom' => 50,
            'cal' => 0,
            'jg' => 2,
            'dtm' => 0,
            'dt' => 0,
        ];

        return http_build_query($parameters).'&body=0&body=1&coord=1&figure=0';
    }

    public static function citationUrl(int $year): string
    {
        return self::BASE_URL.'?'.self::formBody($year);
    }

    /**
     * @return array{body:string,source_url:string,source_accessed_on:string,raw_content_hash:string,from_cache:bool}
     */
    private function readCached(string $rawPath, string $metadataPath, int $year): array
    {
        $body = File::get($rawPath);

        try {
            $metadata = json_decode(File::get($metadataPath), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException("{$year}年のrawメタデータが不正です。", previous: $exception);
        }

        if (! is_array($metadata)
            || ($metadata['year'] ?? null) !== $year
            || ! is_string($metadata['source_accessed_on'] ?? null)
            || ! is_string($metadata['raw_content_hash'] ?? null)
            || ! hash_equals($metadata['raw_content_hash'], hash('sha256', $body))) {
            throw new RuntimeException("{$year}年のrawキャッシュ検証に失敗しました。");
        }

        return [
            'body' => $body,
            'source_url' => self::citationUrl($year),
            'source_accessed_on' => $metadata['source_accessed_on'],
            'raw_content_hash' => $metadata['raw_content_hash'],
            'from_cache' => true,
        ];
    }

    private function writeAtomically(string $path, string $contents): void
    {
        $temporaryPath = $path.'.tmp.'.getmypid();
        File::put($temporaryPath, $contents);

        if (! rename($temporaryPath, $path)) {
            File::delete($temporaryPath);
            throw new RuntimeException("ファイルを配置できません: {$path}");
        }
    }
}
