<?php

namespace App\Filament\Pages;

use App\Services\RfmService;
use App\Settings\GeneralSettings;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class RfmDashboard extends Page
{
    protected static ?string $navigationLabel = 'RFM Dashboard';

    protected static ?string $title = 'RFM Customer Segmentation Dashboard';

    protected static UnitEnum|string|null $navigationGroup = 'Analytics';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ChartBar;

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.pages.rfm-dashboard';

    public array $segmentStats = [];

    public array $previousSegmentStats = [];

    public array $insights = [];

    public array $segmentDefinitions = [];

    public array $metricDefinitions = [];

    public ?string $currentAnalysisDate = null;

    public ?string $previousAnalysisDate = null;

    public function mount(): void
    {
        $settings = app(GeneralSettings::class);
        $rfmService = app(RfmService::class);

        $timeframeDays = $settings->rfm_timeframe_days ?? 365;
        $analysisDate = now();

        // Calculate current period segments
        $this->segmentStats = $rfmService->calculateSegments(
            timeframeDays: $timeframeDays,
            asOfDate: $analysisDate
        );

        $this->currentAnalysisDate = $analysisDate->format('Y-m-d');

        // Calculate previous period for comparison
        $previousDate = $analysisDate->copy()->subDays($timeframeDays);
        $this->previousAnalysisDate = $previousDate->format('Y-m-d');

        $this->previousSegmentStats = $rfmService->calculateSegments(
            timeframeDays: $timeframeDays,
            asOfDate: $previousDate
        );

        // Generate insights
        if (! isset($this->previousSegmentStats['message']) && ! empty($this->previousSegmentStats)) {
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
    }
}
