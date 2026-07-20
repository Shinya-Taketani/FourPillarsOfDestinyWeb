<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\CalendarDataIntegrityException;
use App\Support\SolarTermSourcePriority;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

readonly class SolarTermService
{
    private const TERMS_PER_COMPLETE_YEAR = 24;

    public function getAdoptedSolarTermEvent(string $termName, int $year): ?object
    {
        if (! $this->hasCompleteAdoptedYear($year)) {
            return null;
        }

        $events = DB::table('solar_term_events')
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
            ->whereIn('solar_term_events.source_rank', array_keys(SolarTermSourcePriority::PRIORITIES))
            ->orderBy('solar_term_events.started_at')
            ->get();

        if ($events->count() > 1) {
            throw CalendarDataIntegrityException::forDuplicateAdoptedEvent($termName, $year);
        }

        return $events->first();
    }

    public function getLichunDateTime(int $year): ?CarbonImmutable
    {
        $event = $this->getAdoptedSolarTermEvent('立春', $year);

        if (! $event) {
            return null;
        }

        return CarbonImmutable::parse($event->started_at, $event->timezone);
    }

    public function getLatestMonthBoundaryEvent(CarbonImmutable $dateTime): ?object
    {
        if (! $this->hasCompleteAdoptedYear($dateTime->year)) {
            return null;
        }

        $event = DB::table('solar_term_events')
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
            ->whereIn('solar_term_events.source_rank', array_keys(SolarTermSourcePriority::PRIORITIES))
            ->where('solar_term_definitions.is_month_boundary', true)
            ->where('solar_term_events.started_at', '<=', $dateTime->toDateTimeString())
            ->orderBy('solar_term_events.started_at', 'desc')
            ->first();

        return $event !== null && $this->hasCompleteAdoptedYear((int) $event->year) ? $event : null;
    }

    public function getNextMonthBoundaryEvent(CarbonImmutable $dateTime): ?object
    {
        if (! $this->hasCompleteAdoptedYear($dateTime->year)) {
            return null;
        }

        $event = DB::table('solar_term_events')
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
            ->whereIn('solar_term_events.source_rank', array_keys(SolarTermSourcePriority::PRIORITIES))
            ->where('solar_term_definitions.is_month_boundary', true)
            ->where('solar_term_events.started_at', '>', $dateTime->toDateTimeString())
            ->orderBy('solar_term_events.started_at')
            ->first();

        return $event !== null && $this->hasCompleteAdoptedYear((int) $event->year) ? $event : null;
    }

    public function getPreviousMonthBoundaryEvent(CarbonImmutable $dateTime): ?object
    {
        if (! $this->hasCompleteAdoptedYear($dateTime->year)) {
            return null;
        }

        $event = DB::table('solar_term_events')
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
            ->whereIn('solar_term_events.source_rank', array_keys(SolarTermSourcePriority::PRIORITIES))
            ->where('solar_term_definitions.is_month_boundary', true)
            ->where('solar_term_events.started_at', '<=', $dateTime->toDateTimeString())
            ->orderBy('solar_term_events.started_at', 'desc')
            ->first();

        return $event !== null && $this->hasCompleteAdoptedYear((int) $event->year) ? $event : null;
    }

    public function getMonthBoundaryEvents(int $year): Collection
    {
        if (! $this->hasCompleteAdoptedYear($year)) {
            return collect();
        }

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
            ->whereIn('solar_term_events.source_rank', array_keys(SolarTermSourcePriority::PRIORITIES))
            ->where('solar_term_definitions.is_month_boundary', true)
            ->orderBy('solar_term_definitions.display_order')
            ->get();
    }

    private function hasCompleteAdoptedYear(int $year): bool
    {
        $counts = DB::table('solar_term_events')
            ->where('year', $year)
            ->where('adopted', true)
            ->whereIn('source_rank', array_keys(SolarTermSourcePriority::PRIORITIES))
            ->selectRaw('COUNT(*) AS total, COUNT(DISTINCT solar_term_definition_id) AS distinct_total')
            ->first();

        if ((int) $counts->total !== (int) $counts->distinct_total) {
            throw CalendarDataIntegrityException::forDuplicateAdoptedYear($year);
        }

        return (int) $counts->distinct_total === self::TERMS_PER_COMPLETE_YEAR;
    }
}
