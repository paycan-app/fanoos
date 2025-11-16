<?php

namespace App\Filament\Pages;

use App\Services\RfmService;
use App\Settings\RfmSettingsContract;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class CustomerSegments extends Page
{
    protected static ?string $navigationLabel = 'Customer Segments';

    protected static ?string $title = 'RFM Customer Segmentation';

    protected static UnitEnum|string|null $navigationGroup = 'Analytics';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ChartBar;

    protected static ?int $navigationSort = -10;

    protected string $view = 'filament.pages.rfm-dashboard';

    public array $segmentStats = [];

    public array $previousSegmentStats = [];

    public array $insights = [];

    public array $segmentDefinitions = [];

    public array $metricDefinitions = [];

    public ?string $currentAnalysisDate = null;

    public ?string $previousAnalysisDate = null;

    public array $summary = [];

    public array $topSegments = [];

    public array $segmentMomentum = [];

    public array $winBackTargets = [];

    public ?string $statusMessage = null;

    public string $currencyCode = 'USD';

    public string $currencySymbol = '$';

    public string $timeframeLabel = '';

    public function mount(): void
    {
        /** @var RfmSettingsContract $settings */
        $settings = app(RfmSettingsContract::class);
        $rfmService = app(RfmService::class);

        $timeframeDays = $settings->getRfmTimeframeDays() ?? 365;
        $analysisDate = now();

        // Calculate current period segments
        $currentSegments = $rfmService->calculateSegments(
            timeframeDays: $timeframeDays,
            asOfDate: $analysisDate
        );

        if (isset($currentSegments['message']) || empty($currentSegments)) {
            $this->statusMessage = $currentSegments['message'] ?? 'Please run the RFM calculation from the Setup Wizard first.';
            $this->segmentStats = [];
            $this->summary = $rfmService->summarizeSegments([]);
            $this->currencyCode = $rfmService->getCurrencyCode();
            $this->currencySymbol = $this->resolveCurrencySymbol($this->currencyCode);

            return;
        }

        $this->segmentStats = $currentSegments;

        $this->currentAnalysisDate = $analysisDate->format('Y-m-d');

        // Calculate previous period for comparison
        $previousDate = $analysisDate->copy()->subDays($timeframeDays);
        $this->previousAnalysisDate = $previousDate->format('Y-m-d');

        $previousSegments = $rfmService->calculateSegments(
            timeframeDays: $timeframeDays,
            asOfDate: $previousDate
        );
        $this->previousSegmentStats = isset($previousSegments['message']) ? [] : $previousSegments;

        // Generate insights
        if (! empty($this->previousSegmentStats)) {
            $this->insights = $rfmService->getInsights(
                $this->segmentStats,
                $this->previousSegmentStats,
                $analysisDate,
                $previousDate
            );
        }

        // Load definitions
        $this->segmentDefinitions = $rfmService->getSegmentDefinitions();
        $this->metricDefinitions = $rfmService->getMetricDefinitions();

        $this->summary = $rfmService->summarizeSegments($this->segmentStats);
        $this->currencyCode = $this->summary['currency'] ?? $rfmService->getCurrencyCode();
        $this->currencySymbol = $this->resolveCurrencySymbol($this->currencyCode);
        $this->topSegments = $this->summary['top_segments'] ?? [];
        $this->segmentMomentum = $this->buildSegmentMomentum($this->segmentStats, $this->previousSegmentStats);
        $this->winBackTargets = $this->buildWinBackTargets($this->segmentStats);
        $this->timeframeLabel = sprintf('Last %d days', $timeframeDays);
    }

    protected function buildSegmentMomentum(array $current, array $previous): array
    {
        if (empty($current)) {
            return [];
        }

        $currentMap = collect($current)->keyBy('segment');
        $previousMap = collect($previous)->keyBy('segment');

        return $currentMap
            ->map(function ($row, $segment) use ($previousMap) {
                $previousCustomers = $previousMap[$segment]['customers'] ?? 0;
                $deltaCustomers = $row['customers'] - $previousCustomers;
                $deltaPercent = $previousCustomers > 0
                    ? round(($deltaCustomers / $previousCustomers) * 100, 1)
                    : null;

                return [
                    'segment' => $segment,
                    'customers' => $row['customers'],
                    'avg_monetary' => $row['avg_monetary'],
                    'avg_frequency' => $row['avg_frequency'],
                    'delta_customers' => $deltaCustomers,
                    'delta_percent' => $deltaPercent,
                    'trend' => $deltaCustomers <=> 0,
                ];
            })
            ->sortByDesc('customers')
            ->values()
            ->all();
    }

    protected function buildWinBackTargets(array $current): array
    {
        if (empty($current)) {
            return [];
        }

        $riskSegments = [
            'At Risk',
            'About To Sleep',
            'Cannot Lose Them',
            'Hibernating',
            'Lost',
        ];

        return collect($current)
            ->filter(fn ($row) => in_array($row['segment'], $riskSegments, true))
            ->map(fn ($row) => [
                'segment' => $row['segment'],
                'customers' => $row['customers'],
                'avg_monetary' => $row['avg_monetary'],
                'potential_revenue' => $row['customers'] * $row['avg_monetary'],
            ])
            ->sortByDesc('potential_revenue')
            ->take(4)
            ->values()
            ->all();
    }

    protected function resolveCurrencySymbol(string $currency): string
    {
        return match (strtoupper($currency)) {
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥',
            'AUD' => 'A$',
            'CAD' => 'C$',
            default => strtoupper($currency).' ',
        };
    }
}
