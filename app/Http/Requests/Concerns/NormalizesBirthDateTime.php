<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

trait NormalizesBirthDateTime
{
    /**
     * 既存 UI の datetime-local 値を birthday / birth_time に分ける。
     *
     * @return array{0:?string,1:?string}
     */
    protected function splitBirthDateTime(?string $birthday, ?string $birthTime): array
    {
        if ($birthTime !== null || $birthday === null) {
            return [$birthday, $birthTime];
        }

        if (str_contains($birthday, 'T')) {
            [$date, $time] = explode('T', $birthday, 2);

            return [$date, substr($time, 0, 5)];
        }

        if (preg_match('/^(\d{4}-\d{2}-\d{2})\s+(\d{2}:\d{2})/', $birthday, $matches) === 1) {
            return [$matches[1], $matches[2]];
        }

        return [$birthday, $birthTime];
    }

    /** @param array{birthday:string,birth_time:string} $data */
    protected function combineBirthDateTime(array $data): string
    {
        return $data['birthday'].'T'.$data['birth_time'];
    }

    /** @param array{target_datetime?:mixed,target_year?:mixed} $data */
    protected function resolveTargetDateTime(array $data): ?string
    {
        if (! empty($data['target_datetime'])) {
            return $data['target_datetime'];
        }

        if (! empty($data['target_year'])) {
            return $data['target_year'].'-07-01T00:00';
        }

        return null;
    }
}
