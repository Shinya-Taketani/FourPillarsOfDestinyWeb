<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;

final class CalendarCoverageService
{
    public const BIRTH_MIN_YEAR = 1900;

    public const BIRTH_MAX_YEAR = 2100;

    public const INTERNAL_MIN_YEAR = 1899;

    public const INTERNAL_MAX_YEAR = 2101;

    private const TERMS_PER_YEAR = 24;

    /**
     * @return array{
     *     birth_date:array{min_year:int,max_year:int},
     *     internal_data:array{min_year:?int,max_year:?int},
     *     complete:bool,
     *     missing_years:list<int>
     * }
     */
    public function getCoverage(): array
    {
        $completeYears = DB::table('solar_term_events')
            ->select('year')
            ->where('adopted', true)
            ->whereBetween('year', [self::INTERNAL_MIN_YEAR, self::INTERNAL_MAX_YEAR])
            ->groupBy('year')
            ->havingRaw('COUNT(DISTINCT solar_term_definition_id) = ?', [self::TERMS_PER_YEAR])
            ->orderBy('year')
            ->pluck('year')
            ->map(static fn (mixed $year): int => (int) $year)
            ->all();

        $expectedYears = range(self::INTERNAL_MIN_YEAR, self::INTERNAL_MAX_YEAR);
        $missingYears = array_values(array_diff($expectedYears, $completeYears));

        return [
            'birth_date' => [
                'min_year' => self::BIRTH_MIN_YEAR,
                'max_year' => self::BIRTH_MAX_YEAR,
            ],
            'internal_data' => [
                'min_year' => $completeYears === [] ? null : min($completeYears),
                'max_year' => $completeYears === [] ? null : max($completeYears),
            ],
            'complete' => $missingYears === [],
            'missing_years' => $missingYears,
        ];
    }
}
