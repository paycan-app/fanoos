<?php

namespace App\Services;

use App\Models\Customer;
use App\Settings\RfmSettingsContract;
use Carbon\Carbon;

class RfmService
{
    protected string $currencyCode;

    public function __construct(
        protected RfmSettingsContract $settings
    ) {
        $this->currencyCode = config('app.currency', 'USD');
    }

    public function calculateSegments(?int $timeframeDays = null, ?Carbon $asOfDate = null): array
    {
        // Removed: if (! $this->settings->rfm_enable) { return ['message' => 'RFM is disabled in settings.']; }

        $bins = $this->determineBinCount();
        $segments = $this->determineSegmentCount();
        $timeframe = $this->determineTimeframeDays($timeframeDays);
        $analysisDate = $asOfDate ?? now();

        $customers = Customer::query()->with('orders')->get();

        if ($customers->isEmpty()) {
            return [
                'message' => 'No customers available for RFM analysis yet.',
            ];
        }

        $recencies = [];
        $frequencies = [];
        $monetaries = [];

        $customerMetrics = [];
        foreach ($customers as $c) {
            $r = $this->calculateRecency($c, $timeframe, $analysisDate);
            $f = $this->calculateFrequency($c, $timeframe, $analysisDate);
            $m = $this->calculateMonetary($c, $timeframe, $analysisDate);

            $customerMetrics[$c->id] = [
                'recency' => $r,
                'frequency' => $f,
                'monetary' => $m,
            ];

            if ($r !== null) {
                $recencies[] = $r;
            }
            $frequencies[] = $f;
            $monetaries[] = $m;
        }

        $totalFrequency = array_sum($frequencies);
        $totalMonetary = array_sum($monetaries);

        if ($totalFrequency === 0 && (float) $totalMonetary === 0.0) {
            return [
                'message' => 'No order activity detected within the selected timeframe.',
            ];
        }

        $rBreaks = $this->quantileBreaks($recencies, $bins);
        $fBreaks = $this->quantileBreaks($frequencies, $bins);
        $mBreaks = $this->quantileBreaks($monetaries, $bins);

        $stats = [];

        foreach ($customers as $c) {
            $metrics = $customerMetrics[$c->id];
            $r = $metrics['recency'];
            $f = $metrics['frequency'];
            $m = $metrics['monetary'];

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

    public function summarizeSegments(array $segmentStats, ?string $currency = null): array
    {
        if (empty($segmentStats) || isset($segmentStats['message'])) {
            return [
                'has_data' => false,
                'currency' => $currency ?? $this->currencyCode,
                'total_customers' => 0,
                'total_revenue' => [
                    'value' => 0.0,
                    'formatted' => $this->formatCurrency(0.0, $currency),
                ],
                'average_value' => [
                    'value' => 0.0,
                    'formatted' => $this->formatCurrency(0.0, $currency),
                ],
                'active_segments' => 0,
                'high_value_share' => 0.0,
                'top_segments' => [],
            ];
        }

        $currency ??= $this->currencyCode;

        $statsCollection = collect($segmentStats);
        $totalCustomers = (int) $statsCollection->sum('customers');
        $totalRevenue = (float) $statsCollection->sum(fn ($row) => $row['customers'] * $row['avg_monetary']);
        $averageValue = $totalCustomers > 0 ? $totalRevenue / $totalCustomers : 0.0;

        $highValueRevenue = (float) $statsCollection
            ->whereIn('segment', $this->highValueSegments())
            ->sum(fn ($row) => $row['customers'] * $row['avg_monetary']);

        return [
            'has_data' => true,
            'currency' => $currency,
            'total_customers' => $totalCustomers,
            'total_revenue' => [
                'value' => round($totalRevenue, 2),
                'formatted' => $this->formatCurrency($totalRevenue, $currency),
            ],
            'average_value' => [
                'value' => round($averageValue, 2),
                'formatted' => $this->formatCurrency($averageValue, $currency),
            ],
            'active_segments' => $statsCollection->count(),
            'high_value_share' => $totalRevenue > 0
                ? round(($highValueRevenue / $totalRevenue) * 100, 2)
                : 0.0,
            'top_segments' => $statsCollection
                ->sortByDesc(fn ($row) => $row['customers'] * $row['avg_monetary'])
                ->take(3)
                ->values()
                ->all(),
        ];
    }

    public function getCurrencyCode(): string
    {
        return $this->currencyCode;
    }

    protected function calculateRecency(Customer $customer, int $timeframeDays, Carbon $asOfDate): ?int
    {
        $cutoffDate = $asOfDate->copy()->subDays($timeframeDays);
        $lastOrderDate = $customer->orders()
            ->where('created_at', '>=', $cutoffDate)
            ->where('created_at', '<=', $asOfDate)
            ->max('created_at');

        if (! $lastOrderDate) {
            return null;
        }

        return (int) Carbon::parse($lastOrderDate)->diffInDays($asOfDate);
    }

    protected function calculateFrequency(Customer $customer, int $timeframeDays, Carbon $asOfDate): int
    {
        $cutoffDate = $asOfDate->copy()->subDays($timeframeDays);

        return (int) $customer->orders()
            ->where('created_at', '>=', $cutoffDate)
            ->where('created_at', '<=', $asOfDate)
            ->count();
    }

    protected function calculateMonetary(Customer $customer, int $timeframeDays, Carbon $asOfDate): float
    {
        $cutoffDate = $asOfDate->copy()->subDays($timeframeDays);

        return (float) $customer->orders()
            ->where('created_at', '>=', $cutoffDate)
            ->where('created_at', '<=', $asOfDate)
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
        // Champions: Best customers - High R, F, M
        if ($r >= 4 && $f >= 4 && $m >= 4) {
            return 'Champions';
        }

        // Loyal Customers: Recent and frequent buyers - High F, M
        if ($r >= 3 && $f >= 4 && $m >= 4) {
            return 'Loyal Customers';
        }

        // Potential Loyalist: Recent customers with good potential
        if ($r >= 4 && $f >= 3 && $m >= 3) {
            return 'Potential Loyalist';
        }

        // New Customers: Recently acquired, low frequency
        if ($r >= 4 && $f <= 2 && $m <= 2) {
            return 'New Customers';
        }

        // Promising: Recent customers showing promise
        if ($r >= 3 && $f >= 3 && $m >= 3) {
            return 'Promising';
        }

        // Customers Needing Attention: Recent but low engagement
        if ($r >= 3 && $f <= 2 && $m <= 2) {
            return 'Customers Needing Attention';
        }

        // About To Sleep: Risk of losing, were good customers
        if ($r <= 2 && $f >= 3 && $m >= 3) {
            return 'About To Sleep';
        }

        // At Risk: Not recent, declining engagement
        if ($r <= 2 && $f >= 2 && $m >= 2) {
            return 'At Risk';
        }

        // Cannot Lose Them: High value but haven't purchased recently
        if ($r <= 2 && $f <= 2 && $m >= 4) {
            return 'Cannot Lose Them';
        }

        // Hibernating: Very inactive
        if ($r <= 1 && $f <= 2) {
            return 'Hibernating';
        }

        // Lost: No recent activity
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

    public function classifySegmentsMap(?int $timeframeDays = null, bool $save = false, ?Carbon $asOfDate = null): array
    {
        // Removed: if (! $this->settings->rfm_enable) { return []; }

        $bins = $this->determineBinCount();
        $segmentCount = $this->determineSegmentCount();
        $timeframe = $this->determineTimeframeDays($timeframeDays);
        $analysisDate = $asOfDate ?? now();

        $customers = Customer::query()->with('orders')->get();

        $recencies = [];
        $frequencies = [];
        $monetaries = [];

        foreach ($customers as $c) {
            $r = $this->calculateRecency($c, $timeframe, $analysisDate);
            $f = $this->calculateFrequency($c, $timeframe, $analysisDate);
            $m = $this->calculateMonetary($c, $timeframe, $analysisDate);

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
            $r = $this->calculateRecency($c, $timeframe, $analysisDate);
            $f = $this->calculateFrequency($c, $timeframe, $analysisDate);
            $m = $this->calculateMonetary($c, $timeframe, $analysisDate);

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

    public function buildMarimekkoByMonetary(?int $timeframeDays = null, ?Carbon $asOfDate = null): array
    {
        // Removed: if (! $this->settings->rfm_enable) { return ['segments' => [], 'binLabels' => [], 'total' => 0]; }

        $bins = $this->determineBinCount();
        $timeframe = $this->determineTimeframeDays($timeframeDays);

        $map = $this->classifySegmentsMap($timeframe, save: false, asOfDate: $asOfDate);
        if (empty($map)) {
            return ['segments' => [], 'binLabels' => [], 'total' => 0];
        }

        $mValues = array_map(fn ($row) => $row['m'], $map);
        $mBreaks = $this->quantileBreaks($mValues, $bins);
        $binLabels = [];
        for ($i = 1; $i <= $bins; $i++) {
            $binLabels[] = 'M'.$i;
        }

        $segmentCounts = [];
        $segmentsBins = [];

        foreach ($map as $row) {
            $segment = $row['segment'];
            $scoreM = $this->scoreValue($row['m'], $mBreaks, $bins);
            $label = 'M'.$scoreM;

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
        // Removed: if (! $this->settings->rfm_enable) { return ['labels' => [], 'matrix' => [], 'total' => 0]; }

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
        // Removed: if (! $this->settings->rfm_enable) { return []; }

        $bins = $this->determineBinCount();
        $segmentCount = $this->determineSegmentCount();

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
        // Removed: if (! $this->settings->rfm_enable) { return ['labels' => [], 'matrix' => [], 'total' => 0]; }

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

        $days = $this->determineTimeframeDays();

        $baselineStart = $asOfA->copy()->subDays($days)->startOfDay();
        $baselineEnd = $asOfA;

        $comparisonStart = $asOfB->copy()->subDays($days)->startOfDay();
        $comparisonEnd = $asOfB;

        return $this->buildTransitionsMatrixForIntervals($baselineStart, $baselineEnd, $comparisonStart, $comparisonEnd);
    }

    /**
     * Get segment definitions with business context for tooltips
     */
    public function getSegmentDefinitions(): array
    {
        $segmentCount = $this->determineSegmentCount();

        return match ($segmentCount) {
            3 => $this->getThreeSegmentDefinitions(),
            5 => $this->getFiveSegmentDefinitions(),
            11 => $this->getElevenSegmentDefinitions(),
            default => $this->getFiveSegmentDefinitions(),
        };
    }

    protected function getThreeSegmentDefinitions(): array
    {
        return [
            'High Value' => [
                'description' => 'Your best customers with high overall scores',
                'criteria' => 'Average RFM score ≥ 4',
                'business_action' => 'Focus: VIP treatment, exclusive offers, early access to new products',
                'color' => 'green',
            ],
            'Medium Value' => [
                'description' => 'Moderate engagement customers with growth potential',
                'criteria' => 'Average RFM score between 2.5 and 4',
                'business_action' => 'Focus: Upsell opportunities, engagement campaigns, loyalty programs',
                'color' => 'yellow',
            ],
            'Low Value' => [
                'description' => 'Low engagement or at-risk customers',
                'criteria' => 'Average RFM score < 2.5',
                'business_action' => 'Focus: Win-back campaigns, reactivation offers, feedback surveys',
                'color' => 'red',
            ],
        ];
    }

    protected function getFiveSegmentDefinitions(): array
    {
        return [
            'Champions' => [
                'description' => 'Your best customers! Recent buyers, frequent orders, high spend',
                'criteria' => 'R ≥ 4, F ≥ 4, M ≥ 4',
                'business_action' => 'Focus: Retention, VIP treatment, referral programs, exclusive rewards',
                'color' => 'green',
            ],
            'Loyal Customers' => [
                'description' => 'Regular customers with high value and frequency',
                'criteria' => 'F ≥ 4, M ≥ 4',
                'business_action' => 'Focus: Upsell, cross-sell, loyalty programs, personalized offers',
                'color' => 'blue',
            ],
            'Potential Loyalist' => [
                'description' => 'Recent customers showing good potential',
                'criteria' => 'R ≥ 4, (F ≥ 3 OR M ≥ 3)',
                'business_action' => 'Focus: Engagement campaigns, product recommendations, build relationship',
                'color' => 'cyan',
            ],
            'At Risk' => [
                'description' => 'Haven\'t purchased recently, risk of churn',
                'criteria' => 'R ≤ 2',
                'business_action' => 'Focus: Win-back campaigns, special discounts, re-engagement emails',
                'color' => 'orange',
            ],
            'Need Attention' => [
                'description' => 'Below average customers requiring nurturing',
                'criteria' => 'All others not matching above criteria',
                'business_action' => 'Focus: Surveys, feedback collection, targeted campaigns, value demonstration',
                'color' => 'yellow',
            ],
        ];
    }

    protected function getElevenSegmentDefinitions(): array
    {
        return [
            'Champions' => [
                'description' => 'Your absolute best customers - high recency, frequency, and monetary',
                'criteria' => 'R ≥ 4, F ≥ 4, M ≥ 4',
                'business_action' => 'Reward them! VIP programs, early access, exclusive offers, referral incentives',
                'color' => 'green',
            ],
            'Loyal Customers' => [
                'description' => 'Regular high-value customers who buy frequently',
                'criteria' => 'R ≥ 3, F ≥ 4, M ≥ 4',
                'business_action' => 'Upsell premium products, cross-sell, loyalty rewards, personalization',
                'color' => 'blue',
            ],
            'Potential Loyalist' => [
                'description' => 'Recent customers with good spending and frequency',
                'criteria' => 'R ≥ 4, F ≥ 3, M ≥ 3',
                'business_action' => 'Build relationship, recommend products, engage on social media',
                'color' => 'cyan',
            ],
            'New Customers' => [
                'description' => 'Recently acquired but haven\'t made many purchases yet',
                'criteria' => 'R ≥ 4, F ≤ 2, M ≤ 2',
                'business_action' => 'Onboard properly, provide support, encourage second purchase',
                'color' => 'purple',
            ],
            'Promising' => [
                'description' => 'Recent buyers showing consistent engagement',
                'criteria' => 'R ≥ 3, F ≥ 3, M ≥ 3',
                'business_action' => 'Create brand advocates, engage frequently, offer value',
                'color' => 'indigo',
            ],
            'Customers Needing Attention' => [
                'description' => 'Recent but low engagement - need nurturing',
                'criteria' => 'R ≥ 3, F ≤ 2, M ≤ 2',
                'business_action' => 'Gather feedback, address concerns, demonstrate value',
                'color' => 'yellow',
            ],
            'About To Sleep' => [
                'description' => 'Risk of losing - were good customers but becoming inactive',
                'criteria' => 'R ≤ 2, F ≥ 3, M ≥ 3',
                'business_action' => 'Win-back campaigns, reactivation offers, personalized outreach',
                'color' => 'orange',
            ],
            'At Risk' => [
                'description' => 'Not recent, declining engagement - intervention needed',
                'criteria' => 'R ≤ 2, F ≥ 2, M ≥ 2',
                'business_action' => 'Limited time offers, surveys, re-engagement series',
                'color' => 'red-light',
            ],
            'Cannot Lose Them' => [
                'description' => 'High spenders who haven\'t purchased recently - critical!',
                'criteria' => 'R ≤ 2, F ≤ 2, M ≥ 4',
                'business_action' => 'Aggressive win-back, personal contact, premium incentives',
                'color' => 'red',
            ],
            'Hibernating' => [
                'description' => 'Very inactive customers - difficult to recover',
                'criteria' => 'R ≤ 1, F ≤ 2',
                'business_action' => 'Last-chance campaigns, surveys, consider pruning list',
                'color' => 'gray',
            ],
            'Lost' => [
                'description' => 'No recent activity or zero engagement',
                'criteria' => 'No orders, frequency = 0, or monetary = 0',
                'business_action' => 'Final win-back attempt, then remove from active campaigns',
                'color' => 'gray-dark',
            ],
        ];
    }

    /**
     * Generate actionable insights by comparing current and previous RFM analysis
     */
    public function getInsights(array $currentStats, array $previousStats, Carbon $currentDate, Carbon $previousDate): array
    {
        $insights = [];

        // Build lookup maps
        $currentMap = collect($currentStats)->keyBy('segment');
        $previousMap = collect($previousStats)->keyBy('segment');

        // Total customer comparison
        $totalCurrent = collect($currentStats)->sum('customers');
        $totalPrevious = collect($previousStats)->sum('customers');
        $customerGrowth = $totalPrevious > 0 ? (($totalCurrent - $totalPrevious) / $totalPrevious) * 100 : 0;

        // 1. High-value churn detection
        $highValueSegments = ['Champions', 'Loyal Customers', 'Cannot Lose Them', 'High Value'];
        $atRiskSegments = ['At Risk', 'About To Sleep', 'Hibernating', 'Lost', 'Low Value'];

        foreach ($highValueSegments as $segment) {
            $current = $currentMap->get($segment);
            $previous = $previousMap->get($segment);

            if ($current && $previous) {
                $change = $current['customers'] - $previous['customers'];
                $percentChange = $previous['customers'] > 0 ? ($change / $previous['customers']) * 100 : 0;

                if ($change < 0 && abs($percentChange) > 10) {
                    $insights[] = [
                        'type' => 'alert',
                        'icon' => '⚠️',
                        'title' => "High-Value Customer Decline in {$segment}",
                        'message' => abs($change)." customers left {$segment} segment (".round($percentChange, 1).'% decline)',
                        'tooltip' => "Compared {$segment} segment between ".
                            $previousDate->format('M d, Y')." ({$previous['customers']} customers) and ".
                            $currentDate->format('M d, Y')." ({$current['customers']} customers). ".
                            'This represents a potential revenue risk of $'.number_format(abs($change) * ($previous['avg_monetary'] ?? 0), 2),
                        'priority' => 'high',
                    ];
                }
            }
        }

        // 2. Upgrade opportunities
        $upgradeableSegments = ['Potential Loyalist', 'Promising', 'Need Attention', 'Medium Value'];
        foreach ($upgradeableSegments as $segment) {
            $current = $currentMap->get($segment);
            if ($current && $current['customers'] > 0 && $current['customers'] >= 10) {
                $insights[] = [
                    'type' => 'opportunity',
                    'icon' => '💡',
                    'title' => "Upgrade Opportunity in {$segment}",
                    'message' => "{$current['customers']} customers ready for engagement to move to higher tiers",
                    'tooltip' => "These {$current['customers']} customers in {$segment} show strong potential. ".
                        'Average spend: $'.number_format($current['avg_monetary'], 2).'. '.
                        'Focus on personalized campaigns and upsell opportunities.',
                    'priority' => 'medium',
                ];
                break; // Only show one opportunity to avoid clutter
            }
        }

        // 3. Win-back targets
        foreach ($atRiskSegments as $segment) {
            $current = $currentMap->get($segment);
            if ($current && $current['customers'] > 0 && in_array($segment, ['Lost', 'Hibernating'])) {
                $potentialRevenue = $current['customers'] * ($current['avg_monetary'] ?? 0);
                if ($potentialRevenue > 1000) {
                    $insights[] = [
                        'type' => 'action',
                        'icon' => '📧',
                        'title' => "Win-Back Campaign Recommended for {$segment}",
                        'message' => "{$current['customers']} inactive customers represent $".number_format($potentialRevenue, 2).' potential recovery',
                        'tooltip' => "Customers in {$segment} segment haven't purchased recently but have historical value. ".
                            'Recommended action: Launch targeted win-back campaign with special incentives. '.
                            'Average historical spend: $'.number_format($current['avg_monetary'], 2).'.',
                        'priority' => 'medium',
                    ];
                    break;
                }
            }
        }

        // 4. Recovery successes
        $successSegments = ['Champions', 'Loyal Customers', 'Promising'];
        foreach ($successSegments as $segment) {
            $current = $currentMap->get($segment);
            $previous = $previousMap->get($segment);

            if ($current && $previous) {
                $change = $current['customers'] - $previous['customers'];
                $percentChange = $previous['customers'] > 0 ? ($change / $previous['customers']) * 100 : 0;

                if ($change > 0 && $percentChange > 15) {
                    $insights[] = [
                        'type' => 'success',
                        'icon' => '✅',
                        'title' => "Growth Success in {$segment}",
                        'message' => "{$change} customers upgraded to {$segment} (+".round($percentChange, 1).'%)',
                        'tooltip' => 'Between '.
                            $previousDate->format('M d, Y').' and '.$currentDate->format('M d, Y').', '.
                            "{$change} customers moved into {$segment}. Continue successful strategies that drove this improvement.",
                        'priority' => 'low',
                    ];
                    break;
                }
            }
        }

        // 5. Overall growth/decline
        if (abs($customerGrowth) > 5) {
            $insights[] = [
                'type' => $customerGrowth > 0 ? 'success' : 'alert',
                'icon' => $customerGrowth > 0 ? '📈' : '📉',
                'title' => 'Overall Customer Base '.($customerGrowth > 0 ? 'Growth' : 'Decline'),
                'message' => abs($totalCurrent - $totalPrevious).' customers ('.($customerGrowth > 0 ? '+' : '').round($customerGrowth, 1).'%)',
                'tooltip' => "Total active customers changed from {$totalPrevious} (".
                    $previousDate->format('M d, Y').") to {$totalCurrent} (".
                    $currentDate->format('M d, Y').'). '.
                    'This is calculated by comparing customers with at least one order in each respective analysis period.',
                'priority' => abs($customerGrowth) > 15 ? 'high' : 'medium',
            ];
        }

        // If no insights, provide a positive message
        if (empty($insights)) {
            $insights[] = [
                'type' => 'info',
                'icon' => 'ℹ️',
                'title' => 'Stable Customer Base',
                'message' => 'No significant changes detected between analysis periods',
                'tooltip' => 'Compared RFM analysis from '.
                    $previousDate->format('M d, Y').' to '.$currentDate->format('M d, Y').'. '.
                    'Customer segments remain relatively stable with no major shifts requiring immediate action.',
                'priority' => 'low',
            ];
        }

        // Sort by priority
        $priorityOrder = ['high' => 1, 'medium' => 2, 'low' => 3];
        usort($insights, fn ($a, $b) => ($priorityOrder[$a['priority']] ?? 99) <=> ($priorityOrder[$b['priority']] ?? 99));

        return $insights;
    }

    /**
     * Get metric definitions for tooltips
     */
    public function getMetricDefinitions(): array
    {
        return [
            'recency' => [
                'name' => 'Recency',
                'description' => 'Days since last purchase (lower = more recent = better)',
                'business_meaning' => 'Recent customers are more likely to purchase again and respond to marketing',
                'calculation' => 'Calculated as days between the last order date and analysis date',
            ],
            'frequency' => [
                'name' => 'Frequency',
                'description' => 'Number of orders in analysis period (higher = more loyal)',
                'business_meaning' => 'Frequent buyers are your most engaged and loyal customers',
                'calculation' => 'Total count of orders within the specified timeframe',
            ],
            'monetary' => [
                'name' => 'Monetary',
                'description' => 'Total amount spent in analysis period (higher = more valuable)',
                'business_meaning' => 'High spenders contribute most to revenue and lifetime value',
                'calculation' => 'Sum of all order totals within the specified timeframe',
            ],
            'total_revenue' => [
                'name' => 'Total Revenue',
                'description' => 'Sum of all customer lifetime values in analysis period',
                'business_meaning' => 'Represents total sales generated by active customers',
                'calculation' => 'Sum of all customers\' monetary values across all segments',
            ],
            'total_customers' => [
                'name' => 'Total Customers',
                'description' => 'Active customers with at least one order in timeframe',
                'business_meaning' => 'Your engaged customer base size during the analysis period',
                'calculation' => 'Count of unique customers with frequency > 0',
            ],
            'avg_value' => [
                'name' => 'Average Customer Value',
                'description' => 'Average monetary spend per customer (total revenue ÷ customers)',
                'business_meaning' => 'Indicates the typical value each customer brings to your business',
                'calculation' => 'Total Revenue divided by Total Customers',
            ],
            'active_segments' => [
                'name' => 'Active Segments',
                'description' => 'Number of segments with customers (out of maximum possible)',
                'business_meaning' => 'Shows diversity of your customer base across different behavior patterns',
                'calculation' => 'Count of segments with at least one customer',
            ],
        ];
    }

    protected function determineBinCount(): int
    {
        return max(2, min(9, $this->settings->getRfmBins()));
    }

    protected function determineSegmentCount(): int
    {
        return max(3, min(11, $this->settings->getRfmSegments()));
    }

    protected function determineTimeframeDays(?int $override = null): int
    {
        $days = $override ?? $this->settings->getRfmTimeframeDays();

        return max(1, (int) $days);
    }

    protected function highValueSegments(): array
    {
        return [
            'Champions',
            'Loyal Customers',
            'Potential Loyalist',
            'High Value',
            'Cannot Lose Them',
        ];
    }

    protected function formatCurrency(float $value, ?string $currency = null): string
    {
        $currencyCode = $currency ?? $this->currencyCode;

        try {
            return \Illuminate\Support\Number::currency($value, $currencyCode);
        } catch (\Throwable) {
            return $currencyCode.' '.number_format($value, 2);
        }
    }
}
