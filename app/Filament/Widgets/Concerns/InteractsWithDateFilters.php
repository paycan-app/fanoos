<?php

namespace App\Filament\Widgets\Concerns;

use Carbon\CarbonImmutable;

trait InteractsWithDateFilters
{
    /**
     * @return array{start: CarbonImmutable, end: CarbonImmutable, days: int}
     */
    protected function resolvePrimaryRange(int $fallbackDays = 30): array
    {
        $filters = $this->pageFilters ?? [];

        $end = $this->parseDate($filters['range_end'] ?? null, asEndOfDay: true) ?? now()->endOfDay();
        $start = $this->parseDate($filters['range_start'] ?? null) ?? $end->subDays(max($fallbackDays - 1, 0))->startOfDay();

        if ($start->greaterThan($end)) {
            [$start, $end] = [$end->subDays(max($fallbackDays - 1, 0))->startOfDay(), $end];
        }

        $days = $start->diffInDays($end) + 1;

        return [
            'start' => $start,
            'end' => $end,
            'days' => $days,
        ];
    }

    /**
     * @param  array{start: CarbonImmutable, end: CarbonImmutable, days?: int}  $primaryRange
     * @return array{start: CarbonImmutable, end: CarbonImmutable, days: int} | null
     */
    protected function resolveComparisonRange(array $primaryRange): ?array
    {
        $filters = $this->pageFilters ?? [];
        $mode = $filters['comparison_mode'] ?? 'previous_period';

        return match ($mode) {
            'previous_period' => $this->previousPeriodRange($primaryRange),
            'previous_year' => [
                'start' => $primaryRange['start']->subYear(),
                'end' => $primaryRange['end']->subYear(),
                'days' => $primaryRange['days'] ?? ($primaryRange['start']->diffInDays($primaryRange['end']) + 1),
            ],
            'custom' => $this->customComparisonRange($filters),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{start: CarbonImmutable, end: CarbonImmutable, days: int} | null
     */
    protected function customComparisonRange(array $filters): ?array
    {
        $start = $this->parseDate($filters['comparison_start'] ?? null);
        $end = $this->parseDate($filters['comparison_end'] ?? null, asEndOfDay: true);

        if (! $start || ! $end) {
            return null;
        }

        if ($start->greaterThan($end)) {
            [$start, $end] = [$end->subDay()->startOfDay(), $start->endOfDay()];
        }

        return [
            'start' => $start,
            'end' => $end,
            'days' => $start->diffInDays($end) + 1,
        ];
    }

    /**
     * @param  array{start: CarbonImmutable, end: CarbonImmutable, days?: int}  $range
     * @return array{start: CarbonImmutable, end: CarbonImmutable, days: int}
     */
    protected function previousPeriodRange(array $range): array
    {
        $duration = $range['days'] ?? ($range['start']->diffInDays($range['end']) + 1);
        $end = $range['start']->subDay()->endOfDay();
        $start = $end->subDays(max($duration - 1, 0))->startOfDay();

        return [
            'start' => $start,
            'end' => $end,
            'days' => $duration,
        ];
    }

    protected function parseDate(?string $value, bool $asEndOfDay = false): ?CarbonImmutable
    {
        if (blank($value)) {
            return null;
        }

        try {
            $date = CarbonImmutable::parse($value);
        } catch (\Throwable) {
            return null;
        }

        return $asEndOfDay ? $date->endOfDay() : $date->startOfDay();
    }
}
