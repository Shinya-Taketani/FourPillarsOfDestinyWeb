<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

readonly class SolarTermService
{
    public function getSolarInfo(CarbonImmutable $lmtDateTime): array
    {
        $term = DB::table('master_solar_terms')
            ->where('started_at', '<=', $lmtDateTime->toDateTimeString())
            ->orderBy('started_at', 'desc')
            ->first();

        // データがない場合のフォールバックにも started_at を追加
        if (!$term) {
            return [
                'term_name' => 'データ範囲外',
                'month_stem_id' => 1,
                'month_branch_id' => 3,
                'started_at' => $lmtDateTime->toDateTimeString(), // 仮の日時
            ];
        }

        return [
            'term_name' => $term->name,
            'month_stem_id' => (int)$term->month_stem_id,
            'month_branch_id' => (int)$term->month_branch_id,
            'started_at' => $term->started_at, // ← これを追加！
        ];
    }

    public function getAdoptedSolarTermEvent(string $termName, int $year): ?object
    {
        return DB::table('solar_term_events')
            ->join(
                'solar_term_definitions',
                'solar_term_definitions.id',
                '=',
                'solar_term_events.solar_term_definition_id'
            )
            ->select(
                'solar_term_events.*',
                'solar_term_definitions.name as term_name',
                'solar_term_definitions.month_branch_id',
                'solar_term_definitions.longitude_degree',
                'solar_term_definitions.display_order',
            )
            ->where('solar_term_definitions.name', $termName)
            ->where('solar_term_events.year', $year)
            ->where('solar_term_events.adopted', true)
            ->orderBy('solar_term_events.started_at')
            ->first();
    }

    public function getLichunDateTime(int $year): ?CarbonImmutable
    {
        $event = $this->getAdoptedSolarTermEvent('立春', $year);

        if (!$event) {
            return null;
        }

        return CarbonImmutable::parse($event->started_at, $event->timezone);
    }

    public function getLatestMonthBoundaryEvent(CarbonImmutable $dateTime): ?object
    {
        return DB::table('solar_term_events')
            ->join(
                'solar_term_definitions',
                'solar_term_definitions.id',
                '=',
                'solar_term_events.solar_term_definition_id'
            )
            ->select(
                'solar_term_events.*',
                'solar_term_definitions.name as term_name',
                'solar_term_definitions.month_branch_id',
                'solar_term_definitions.longitude_degree',
                'solar_term_definitions.display_order',
            )
            ->where('solar_term_events.adopted', true)
            ->where('solar_term_definitions.is_month_boundary', true)
            ->where('solar_term_events.started_at', '<=', $dateTime->toDateTimeString())
            ->orderBy('solar_term_events.started_at', 'desc')
            ->first();
    }

    public function getNextMonthBoundaryEvent(CarbonImmutable $dateTime): ?object
    {
        return DB::table('solar_term_events')
            ->join(
                'solar_term_definitions',
                'solar_term_definitions.id',
                '=',
                'solar_term_events.solar_term_definition_id'
            )
            ->select(
                'solar_term_events.*',
                'solar_term_definitions.name as term_name',
                'solar_term_definitions.month_branch_id',
                'solar_term_definitions.longitude_degree',
                'solar_term_definitions.display_order',
            )
            ->where('solar_term_events.adopted', true)
            ->where('solar_term_definitions.is_month_boundary', true)
            ->where('solar_term_events.started_at', '>', $dateTime->toDateTimeString())
            ->orderBy('solar_term_events.started_at')
            ->first();
    }

    public function getPreviousMonthBoundaryEvent(CarbonImmutable $dateTime): ?object
    {
        return DB::table('solar_term_events')
            ->join(
                'solar_term_definitions',
                'solar_term_definitions.id',
                '=',
                'solar_term_events.solar_term_definition_id'
            )
            ->select(
                'solar_term_events.*',
                'solar_term_definitions.name as term_name',
                'solar_term_definitions.month_branch_id',
                'solar_term_definitions.longitude_degree',
                'solar_term_definitions.display_order',
            )
            ->where('solar_term_events.adopted', true)
            ->where('solar_term_definitions.is_month_boundary', true)
            ->where('solar_term_events.started_at', '<=', $dateTime->toDateTimeString())
            ->orderBy('solar_term_events.started_at', 'desc')
            ->first();
    }

    public function getMonthBoundaryEvents(int $year): Collection
    {
        return DB::table('solar_term_events')
            ->join(
                'solar_term_definitions',
                'solar_term_definitions.id',
                '=',
                'solar_term_events.solar_term_definition_id'
            )
            ->select(
                'solar_term_events.*',
                'solar_term_definitions.name as term_name',
                'solar_term_definitions.month_branch_id',
                'solar_term_definitions.longitude_degree',
                'solar_term_definitions.display_order',
            )
            ->where('solar_term_events.year', $year)
            ->where('solar_term_events.adopted', true)
            ->where('solar_term_definitions.is_month_boundary', true)
            ->orderBy('solar_term_definitions.display_order')
            ->get();
    }
}
