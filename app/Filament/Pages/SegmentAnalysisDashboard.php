<?php

namespace App\Filament\Pages;

use App\Models\Customer;
use App\Services\RfmService;
use App\Settings\GeneralSettings;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
// Switch to Schemas components (aligns with Segments.php)
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SegmentAnalysisDashboard extends Page
{
    protected static ?string $navigationLabel = 'Segment Analysis';

    protected static ?string $title = 'Segment Analysis Dashboard';

    protected static string|\UnitEnum|null $navigationGroup = 'Customers';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    public array $segmentStats = [];

    public int $totalCustomers = 0;

    public int $segmentedCustomers = 0;

    public string $analysisDate;

    public function mount(): void
    {
        $this->analysisDate = now()->toDateString();
        $this->calculateSegments();
    }

    public function content(Schema $schema): Schema
    {
        $settings = app(GeneralSettings::class);
        $segmentCount = Customer::whereNotNull('segment')->distinct('segment')->count('segment');
        $coverage = $this->totalCustomers > 0
            ? round(($this->segmentedCustomers / $this->totalCustomers) * 100, 1)
            : 0;

        // Build dynamic distribution list
        $distributionComponents = [];
        foreach ($this->getSegmentDistribution() as $item) {
            $distributionComponents[] = Text::make(
                $item['segment'].': '.number_format($item['customers']).' ('.$item['percentage'].'%)'
            )
                ->badge(true)
                ->color($item['color']);
        }

        return $schema->components([
            Section::make('Segmentation Overview')
                ->description('Current RFM analysis statistics')
                ->icon(Heroicon::InformationCircle)
                ->schema([
                    Grid::make(6)->schema([
                        Text::make('Total Customers: '.number_format($this->totalCustomers))
                            ->badge(true)
                            ->color('success'),
                        Text::make('Segmented Customers: '.number_format($this->segmentedCustomers))
                            ->badge(true)
                            ->color('primary'),
                        Text::make('Active Segments: '.$segmentCount)
                            ->badge(true)
                            ->color('info'),
                        Text::make('Coverage: '.$coverage.'%')
                            ->badge(true)
                            ->color($coverage > 80 ? 'success' : ($coverage > 50 ? 'warning' : 'danger')),
                        Text::make('Segmentation Level: '.$settings->rfm_segments.' segments')
                            ->badge(true)
                            ->color('gray'),
                        Text::make('Timeframe: '.$settings->rfm_timeframe_days.' days')
                            ->badge(true)
                            ->color('gray'),
                    ]),
                ]),

            Section::make('Segment Distribution')
                ->description('Customer distribution across RFM segments')
                ->icon(Heroicon::ChartPie)
                ->schema([
                    Grid::make(2)->schema($distributionComponents),
                ]),

            Section::make('Actionable Insights')
                ->description('Key findings from RFM analysis')
                ->icon(Heroicon::LightBulb)
                ->schema([
                    Grid::make(3)->schema([
                        Text::make('High-Value Segments: Identify and nurture your most valuable customers')
                            ->badge(true)
                            ->color('amber'),
                        Text::make('At-Risk Customers: Re-engage customers showing signs of churn')
                            ->badge(true)
                            ->color('red'),
                        Text::make('Growth Opportunities: Target segments with potential for growth')
                            ->badge(true)
                            ->color('blue'),
                    ]),
                ]),
        ]);
    }

    public function calculateSegments(): void
    {
        $rfmService = app(RfmService::class);
        $this->segmentStats = $rfmService->calculateSegments();

        $this->totalCustomers = Customer::count();
        $this->segmentedCustomers = Customer::whereNotNull('segment')->count();

        Notification::make()
            ->title('Customer segments calculated successfully!')
            ->body("Analysis complete: {$this->totalCustomers} customers segmented.")
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('recalculate')
                ->label('Recalculate Segments')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->action(fn () => $this->calculateSegments()),
        ];
    }

    public function getStats(): array
    {
        $settings = app(GeneralSettings::class);
        $segmentCount = Customer::whereNotNull('segment')->distinct('segment')->count('segment');
        $coverage = $this->totalCustomers > 0
            ? round(($this->segmentedCustomers / $this->totalCustomers) * 100, 1)
            : 0;

        return [
            Stat::make('Total Customers', number_format($this->totalCustomers))
                ->description('All customers in database')
                ->color('success')
                ->icon('heroicon-o-user-group'),

            Stat::make('Segmented Customers', number_format($this->segmentedCustomers))
                ->description('Customers with assigned segments')
                ->color('primary')
                ->icon('heroicon-o-tag'),

            Stat::make('Active Segments', $segmentCount)
                ->description('Unique segment categories')
                ->color('info')
                ->icon('heroicon-o-chart-bar'),

            Stat::make('Coverage', $coverage.'%')
                ->description('Percentage of customers segmented')
                ->color($coverage > 80 ? 'success' : ($coverage > 50 ? 'warning' : 'danger'))
                ->icon('heroicon-o-presentation-chart-line'),

            Stat::make('Segmentation Level', $settings->rfm_segments.' segments')
                ->description('Current RFM configuration')
                ->color('gray')
                ->icon('heroicon-o-cog'),

            Stat::make('Timeframe', $settings->rfm_timeframe_days.' days')
                ->description('Analysis period')
                ->color('gray')
                ->icon('heroicon-o-calendar'),
        ];
    }

    public function getSegmentDistribution(): array
    {
        $distribution = [];
        $total = $this->segmentedCustomers;

        foreach ($this->segmentStats as $segment) {
            $percentage = $total > 0 ? round(($segment['customers'] / $total) * 100, 1) : 0;
            $distribution[] = [
                'segment' => $segment['segment'],
                'customers' => $segment['customers'],
                'percentage' => $percentage,
                'avg_monetary' => $segment['avg_monetary'],
                'avg_frequency' => $segment['avg_frequency'],
                'avg_recency' => $segment['avg_recency'],
                'color' => $this->getSegmentColor($segment['segment']),
            ];
        }

        return $distribution;
    }

    protected function getSegmentColor(string $segment): string
    {
        return match (true) {
            str_contains($segment, 'Champion') => 'warning',
            str_contains($segment, 'Loyal') => 'success',
            str_contains($segment, 'Potential') || str_contains($segment, 'Promising') => 'info',
            str_contains($segment, 'At Risk') || str_contains($segment, 'Sleep') => 'danger',
            str_contains($segment, 'Lost') || str_contains($segment, 'Hibernating') => 'danger',
            str_contains($segment, 'New') => 'primary',
            str_contains($segment, 'High') => 'success',
            str_contains($segment, 'Medium') => 'warning',
            str_contains($segment, 'Low') => 'danger',
            default => 'gray'
        };
    }
    // Ensure the class ends cleanly and no stray lines remain after this brace
}
