<?php

namespace App\Services;

use App\Models\Customer;
use App\Settings\GeneralSettings;
use Carbon\Carbon;

class RfmService
{
    public function __construct(
        protected GeneralSettings $settings
    ) {}

    public function calculateSegments(?int $timeframeDays = null): array
    {
        if (! $this->settings->rfm_enable) {
            return ['message' => 'RFM is disabled in settings.'];
        }

        $bins = max(2, min(9, $this->settings->rfm_bins));
        $segments = max(3, min(11, $this->settings->rfm_segments));
        $timeframe = $timeframeDays ?? $this->settings->rfm_timeframe_days;

        $customers = Customer::query()->with('orders')->get();

        $recencies = [];
        $frequencies = [];
        $monetaries = [];

        foreach ($customers as $c) {
            $r = $this->calculateRecency($c, $timeframe);
            $f = $this->calculateFrequency($c, $timeframe);
            $m = $this->calculateMonetary($c, $timeframe);

            if ($r !== null) {
                $recencies[] = $r;
            }
            $frequencies[] = $f;
            $monetaries[] = $m;
        }

        $rBreaks = $this->quantileBreaks($recencies, $bins);
        $fBreaks = $this->quantileBreaks($frequencies, $bins);
        $mBreaks = $this->quantileBreaks($monetaries, $bins);

        $stats = [];

        foreach ($customers as $c) {
            $r = $this->calculateRecency($c, $timeframe);
            $f = $this->calculateFrequency($c, $timeframe);
            $m = $this->calculateMonetary($c, $timeframe);

            $rScore = $this->scoreValue($r, $rBreaks, $bins, invert: true);
            $fScore = $this->scoreValue($f, $fBreaks, $bins);
            $mScore = $this->scoreValue($m, $mBreaks, $bins);

            $segment = $this->assignSegment($rScore, $fScore, $mScore, $r, $f, $m, $segments);

            $c->segment = $segment;
            $c->save();

            if (! isset($stats[$segment])) {
                $stats[$segment] = [
                    'segment' => $segment,
                    'customers' => 0,
                    'avg_monetary' => 0.0,
                    'avg_frequency' => 0.0,
                    'avg_recency' => 0.0,
                    'sum_monetary' => 0.0,
                    'sum_frequency' => 0,
                    'sum_recency' => 0,
                ];
            }

            $stats[$segment]['customers']++;
            $stats[$segment]['sum_monetary'] += (float) $m;
            $stats[$segment]['sum_frequency'] += (int) $f;
            $stats[$segment]['sum_recency'] += (int) ($r ?? 0);
        }

        foreach ($stats as &$row) {
            $count = max(1, $row['customers']);
            $row['avg_monetary'] = round($row['sum_monetary'] / $count, 2);
            $row['avg_frequency'] = round($row['sum_frequency'] / $count, 2);
            $row['avg_recency'] = round($row['sum_recency'] / $count, 2);
            unset($row['sum_monetary'], $row['sum_frequency'], $row['sum_recency']);
        }

        return collect($stats)->sortByDesc('customers')->values()->toArray();
    }

    protected function calculateRecency(Customer $customer, int $timeframeDays): ?int
    {
        $cutoffDate = now()->subDays($timeframeDays);
        $lastOrderDate = $customer->orders()
            ->where('created_at', '>=', $cutoffDate)
            ->max('created_at');

        if (! $lastOrderDate) {
            return null;
        }

        return now()->diffInDays($lastOrderDate);
    }

    protected function calculateFrequency(Customer $customer, int $timeframeDays): int
    {
        $cutoffDate = now()->subDays($timeframeDays);

        return (int) $customer->orders()
            ->where('created_at', '>=', $cutoffDate)
            ->count();
    }

    protected function calculateMonetary(Customer $customer, int $timeframeDays): float
    {
        $cutoffDate = now()->subDays($timeframeDays);

        return (float) $customer->orders()
            ->where('created_at', '>=', $cutoffDate)
            ->sum('total_amount');
    }

    protected function quantileBreaks(array $values, int $bins): array
    {
        if (empty($values)) {
            return [];
        }

        sort($values);
        $n = count($values);
        $breaks = [];

        for ($k = 1; $k < $bins; $k++) {
            $pos = (int) round(($k / $bins) * ($n - 1));
            $breaks[] = $values[$pos];
        }

        return $breaks;
    }

    protected function scoreValue(?float $value, array $breaks, int $bins, bool $invert = false): int
    {
        if ($value === null) {
            return 1;
        }

        $score = 1;
        foreach ($breaks as $b) {
            if ($value > $b) {
                $score++;
            } else {
                break;
            }
        }

        $score = min($bins, $score);

        return $invert ? ($bins + 1 - $score) : $score;
    }

    protected function assignSegment(
        int $r,
        int $f,
        int $m,
        ?int $recency,
        int $frequency,
        float $monetary,
        int $segmentCount
    ): string {
        if ($recency === null || $frequency === 0 || $monetary === 0.0) {
            return 'Lost';
        }

        return match ($segmentCount) {
            3 => $this->assignThreeSegments($r, $f, $m),
            5 => $this->assignFiveSegments($r, $f, $m),
            11 => $this->assignElevenSegments($r, $f, $m),
            default => $this->assignFiveSegments($r, $f, $m),
        };
    }

    protected function assignThreeSegments(int $r, int $f, int $m): string
    {
        $avgScore = ($r + $f + $m) / 3;

        if ($avgScore >= 4) {
            return 'High Value';
        }

        if ($avgScore >= 2.5) {
            return 'Medium Value';
        }

        return 'Low Value';
    }

    protected function assignFiveSegments(int $r, int $f, int $m): string
    {
        if ($r >= 4 && $f >= 4 && $m >= 4) {
            return 'Champions';
        }

        if ($f >= 4 && $m >= 4) {
            return 'Loyal Customers';
        }

        if ($r >= 4 && ($f >= 3 || $m >= 3)) {
            return 'Potential Loyalist';
        }

        if ($r <= 2) {
            return 'At Risk';
        }

        return 'Need Attention';
    }

    protected function assignElevenSegments(int $r, int $f, int $m): string
    {
        if ($r >= 4 && $f >= 4 && $m >= 4) {
            return 'Champions';
        }

        if ($r >= 3 && $f >= 4 && $m >= 4) {
            return 'Loyal Customers';
        }

        if ($r >= 4 && $f >= 3 && $m >= 3) {
            return 'Potential Loyalist';
        }

        if ($r >= 4 && $f <= 2 && $m <= 2) {
            return 'New Customers';
        }

        if ($r >= 3 && $f >= 3 && $m >= 3) {
            return 'Promising';
        }

        if ($r >= 3 && $f <= 2 && $m <= 2) {
            return 'Need Attention';
        }

        if ($r <= 2 && $f >= 3 && $m >= 3) {
            return 'About To Sleep';
        }

        if ($r <= 2 && $f >= 2 && $m >= 2) {
            return 'At Risk';
        }

        if ($r <= 2 && $f <= 2 && $m >= 3) {
            return 'Cannot Lose Them';
        }

        if ($r <= 1) {
            return 'Hibernating';
        }

        return 'Lost';
    }

    public function getSegmentStats(): array
    {
        return Customer::query()
            ->selectRaw('segment, COUNT(*) as customers')
            ->whereNotNull('segment')
            ->groupBy('segment')
            ->orderByDesc('customers')
            ->get()
            ->map(function ($item) {
                $customers = Customer::where('segment', $item->segment)->get();

                return [
                    'segment' => $item->segment,
                    'customers' => $item->customers,
                    'avg_monetary' => round($customers->avg('monetary'), 2),
                    'avg_frequency' => round($customers->avg('frequency'), 2),
                    'avg_recency' => round($customers->avg('recency'), 2),
                ];
            })
            ->toArray();
    }

    public function classifySegmentsMap(?int $timeframeDays = null, bool $save = false): array
    {
        if (! $this->settings->rfm_enable) {
            return [];
        }

        $bins = max(2, min(9, $this->settings->rfm_bins));
        $segmentCount = max(3, min(11, $this->settings->rfm_segments));
        $timeframe = $timeframeDays ?? $this->settings->rfm_timeframe_days;

        $customers = Customer::query()->with('orders')->get();

        $recencies = [];
        $frequencies = [];
        $monetaries = [];

        foreach ($customers as $c) {
            $r = $this->calculateRecency($c, $timeframe);
            $f = $this->calculateFrequency($c, $timeframe);
            $m = $this->calculateMonetary($c, $timeframe);

            if ($r !== null) {
                $recencies[] = $r;
            }
            $frequencies[] = $f;
            $monetaries[] = $m;
        }

        $rBreaks = $this->quantileBreaks($recencies, $bins);
        $fBreaks = $this->quantileBreaks($frequencies, $bins);
        $mBreaks = $this->quantileBreaks($monetaries, $bins);

        $map = [];

        foreach ($customers as $c) {
            $r = $this->calculateRecency($c, $timeframe);
            $f = $this->calculateFrequency($c, $timeframe);
            $m = $this->calculateMonetary($c, $timeframe);

            $rScore = $this->scoreValue($r, $rBreaks, $bins, invert: true);
            $fScore = $this->scoreValue($f, $fBreaks, $bins);
            $mScore = $this->scoreValue($m, $mBreaks, $bins);

            $segment = $this->assignSegment($rScore, $fScore, $mScore, $r, $f, $m, $segmentCount);

            if ($save) {
                $c->segment = $segment;
                $c->save();
            }

            $map[$c->id] = [
                'segment' => $segment,
                'r' => $r,
                'f' => $f,
                'm' => $m,
                'rScore' => $rScore,
                'fScore' => $fScore,
                'mScore' => $mScore,
            ];
        }

        return $map;
    }

    public function buildMarimekkoByMonetary(?int $timeframeDays = null): array
    {
        if (! $this->settings->rfm_enable) {
            return ['segments' => [], 'binLabels' => [], 'total' => 0];
        }

        $bins = max(2, min(9, $this->settings->rfm_bins));
        $timeframe = $timeframeDays ?? $this->settings->rfm_timeframe_days;

        $map = $this->classifySegmentsMap($timeframe, save: false);
        if (empty($map)) {
            return ['segments' => [], 'binLabels' => [], 'total' => 0];
        }

        $mValues = array_map(fn ($row) => $row['m'], $map);
        $mBreaks = $this->quantileBreaks($mValues, $bins);
        $binLabels = [];
        for ($i = 1; $i <= $bins; $i++) {
            $binLabels[] = 'M' . $i;
        }

        $segmentCounts = [];
        $segmentsBins = [];

        foreach ($map as $row) {
            $segment = $row['segment'];
            $scoreM = $this->scoreValue($row['m'], $mBreaks, $bins);
            $label = 'M' . $scoreM;

            $segmentCounts[$segment] = ($segmentCounts[$segment] ?? 0) + 1;
            $segmentsBins[$segment] = $segmentsBins[$segment] ?? [];
            $segmentsBins[$segment][$label] = ($segmentsBins[$segment][$label] ?? 0) + 1;
        }

        $total = array_sum($segmentCounts);
        $segments = [];

        foreach ($segmentCounts as $segment => $count) {
            $share = $total > 0 ? $count / $total : 0.0;
            $binShares = [];
            foreach ($binLabels as $label) {
                $binCount = $segmentsBins[$segment][$label] ?? 0;
                $binShares[$label] = $count > 0 ? $binCount / $count : 0.0;
            }

            $segments[] = [
                'key' => $segment,
                'customers' => $count,
                'share' => round($share, 6),
                'bins' => $binShares,
            ];
        }

        return [
            'segments' => $segments,
            'binLabels' => $binLabels,
            'total' => $total,
        ];
    }

    public function buildTransitionsMatrix(int $baselineDays, int $comparisonDays): array
    {
        if (! $this->settings->rfm_enable) {
            return ['labels' => [], 'matrix' => [], 'total' => 0];
        }

        $oldMap = $this->classifySegmentsMap($baselineDays, save: false);
        $newMap = $this->classifySegmentsMap($comparisonDays, save: false);

        $labelsSet = [];
        foreach ($oldMap as $row) {
            $labelsSet[$row['segment']] = true;
        }
        foreach ($newMap as $row) {
            $labelsSet[$row['segment']] = true;
        }

        $labels = array_values(array_keys($labelsSet));
        sort($labels);

        $index = [];
        foreach ($labels as $i => $label) {
            $index[$label] = $i;
        }

        $n = count($labels);
        $matrix = array_fill(0, $n, array_fill(0, $n, 0));
        $total = 0;

        foreach ($oldMap as $customerId => $row) {
            $old = $row['segment'];
            $new = $newMap[$customerId]['segment'] ?? $old;

            $i = $index[$old];
            $j = $index[$new];
            $matrix[$i][$j]++;
            $total++;
        }

        return [
            'labels' => $labels,
            'matrix' => $matrix,
            'total' => $total,
        ];
    }

    protected function calculateRecencyForInterval(Customer $customer, Carbon $start, Carbon $end): ?int
    {
        $lastOrderDate = $customer->orders()
            ->whereBetween('created_at', [$start, $end])
            ->max('created_at');

        if (! $lastOrderDate) {
            return null;
        }

        return $end->diffInDays($lastOrderDate);
    }

    protected function calculateFrequencyForInterval(Customer $customer, Carbon $start, Carbon $end): int
    {
        return (int) $customer->orders()
            ->whereBetween('created_at', [$start, $end])
            ->count();
    }

    protected function calculateMonetaryForInterval(Customer $customer, Carbon $start, Carbon $end): float
    {
        return (float) $customer->orders()
            ->whereBetween('created_at', [$start, $end])
            ->sum('total_amount');
    }

    public function classifySegmentsMapForInterval(Carbon $start, Carbon $end, bool $save = false): array
    {
        if (! $this->settings->rfm_enable) {
            return [];
        }

        $bins = max(2, min(9, $this->settings->rfm_bins));
        $segmentCount = max(3, min(11, $this->settings->rfm_segments));

        $customers = Customer::query()->with('orders')->get();

        $recencies = [];
        $frequencies = [];
        $monetaries = [];

        foreach ($customers as $c) {
            $r = $this->calculateRecencyForInterval($c, $start, $end);
            $f = $this->calculateFrequencyForInterval($c, $start, $end);
            $m = $this->calculateMonetaryForInterval($c, $start, $end);

            if ($r !== null) {
                $recencies[] = $r;
            }
            $frequencies[] = $f;
            $monetaries[] = $m;
        }

        $rBreaks = $this->quantileBreaks($recencies, $bins);
        $fBreaks = $this->quantileBreaks($frequencies, $bins);
        $mBreaks = $this->quantileBreaks($monetaries, $bins);

        $map = [];

        foreach ($customers as $c) {
            $r = $this->calculateRecencyForInterval($c, $start, $end);
            $f = $this->calculateFrequencyForInterval($c, $start, $end);
            $m = $this->calculateMonetaryForInterval($c, $start, $end);

            $rScore = $this->scoreValue($r, $rBreaks, $bins, invert: true);
            $fScore = $this->scoreValue($f, $fBreaks, $bins);
            $mScore = $this->scoreValue($m, $mBreaks, $bins);

            $segment = $this->assignSegment($rScore, $fScore, $mScore, $r, $f, $m, $segmentCount);

            if ($save) {
                $c->segment = $segment;
                $c->save();
            }

            $map[$c->id] = [
                'segment' => $segment,
                'r' => $r,
                'f' => $f,
                'm' => $m,
                'rScore' => $rScore,
                'fScore' => $fScore,
                'mScore' => $mScore,
            ];
        }

        return $map;
    }

    public function buildTransitionsMatrixForIntervals(Carbon $baselineStart, Carbon $baselineEnd, Carbon $comparisonStart, Carbon $comparisonEnd): array
    {
        if (! $this->settings->rfm_enable) {
            return ['labels' => [], 'matrix' => [], 'total' => 0];
        }

        $oldMap = $this->classifySegmentsMapForInterval($baselineStart, $baselineEnd, save: false);
        $newMap = $this->classifySegmentsMapForInterval($comparisonStart, $comparisonEnd, save: false);

        $labelsSet = [];
        foreach ($oldMap as $row) {
            $labelsSet[$row['segment']] = true;
        }
        foreach ($newMap as $row) {
            $labelsSet[$row['segment']] = true;
        }

        $labels = array_values(array_keys($labelsSet));
        sort($labels);

        $index = [];
        foreach ($labels as $i => $label) {
            $index[$label] = $i;
        }

        $n = count($labels);
        $matrix = array_fill(0, $n, array_fill(0, $n, 0));
        $total = 0;

        foreach ($oldMap as $customerId => $row) {
            $old = $row['segment'];
            $new = $newMap[$customerId]['segment'] ?? $old;

            $i = $index[$old];
            $j = $index[$new];
            $matrix[$i][$j]++;
            $total++;
        }

        return [
            'labels' => $labels,
            'matrix' => $matrix,
            'total' => $total,
        ];
    }

    public function buildTransitionsMatrixForAsOfDates(string|Carbon $asOfA, string|Carbon $asOfB): array
    {
        $asOfA = $asOfA instanceof Carbon ? $asOfA->copy()->endOfDay() : Carbon::parse($asOfA)->endOfDay();
        $asOfB = $asOfB instanceof Carbon ? $asOfB->copy()->endOfDay() : Carbon::parse($asOfB)->endOfDay();

        $days = max(1, (int) $this->settings->rfm_timeframe_days);

        $baselineStart = $asOfA->copy()->subDays($days)->startOfDay();
        $baselineEnd = $asOfA;

        $comparisonStart = $asOfB->copy()->subDays($days)->startOfDay();
        $comparisonEnd = $asOfB;

        return $this->buildTransitionsMatrixForIntervals($baselineStart, $baselineEnd, $comparisonStart, $comparisonEnd);
    }
}
