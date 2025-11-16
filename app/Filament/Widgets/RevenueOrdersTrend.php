<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\InteractsWithDateFilters;
use App\Services\DashboardAnalyticsService;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class RevenueOrdersTrend extends ChartWidget
{
    use InteractsWithDateFilters;
    use InteractsWithPageFilters;

    protected static bool $isLazy = false;

    protected ?string $heading = 'Revenue & order trend';

    protected ?string $description = 'Daily movement for the active window.';

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = '2m';

    protected function getData(): array
    {
        $range = $this->resolvePrimaryRange();
        $trend = $this->analytics()->revenueOrdersTrend($range);

        return [
            'datasets' => [
                [
                    'type' => 'line',
                    'label' => 'Revenue',
                    'data' => $trend['revenue'],
                    'borderColor' => '#eab308',
                    'backgroundColor' => 'rgba(234,179,8,0.15)',
                    'borderWidth' => 3,
                    'pointRadius' => 0,
                    'fill' => true,
                    'tension' => 0.4,
                    'yAxisID' => 'y1',
                ],
                [
                    'type' => 'bar',
                    'label' => 'Orders',
                    'data' => $trend['orders'],
                    'backgroundColor' => '#1d4ed8',
                    'borderRadius' => 4,
                    'yAxisID' => 'y2',
                    'order' => 1,
                ],
                [
                    'type' => 'line',
                    'label' => 'New customers',
                    'data' => $trend['new_customers'],
                    'borderColor' => '#22c55e',
                    'borderDash' => [6, 6],
                    'pointRadius' => 0,
                    'tension' => 0.3,
                    'yAxisID' => 'y2',
                    'order' => 0,
                ],
            ],
            'labels' => $trend['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'interaction' => [
                'mode' => 'index',
                'intersect' => false,
            ],
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
            ],
            'scales' => [
                'y1' => [
                    'type' => 'linear',
                    'position' => 'left',
                    'ticks' => [
                        'callback' => $this->currencyTickCallback(),
                    ],
                    'grid' => [
                        'display' => true,
                    ],
                ],
                'y2' => [
                    'type' => 'linear',
                    'position' => 'right',
                    'grid' => [
                        'display' => false,
                    ],
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
        ];
    }

    protected function currencyTickCallback(): string
    {
        $currency = config('app.currency', 'USD');

        return sprintf(
            'function(value){return new Intl.NumberFormat(undefined,{style:"currency",currency:"%s",maximumFractionDigits:0}).format(value);}',
            $currency,
        );
    }

    protected function analytics(): DashboardAnalyticsService
    {
        return app(DashboardAnalyticsService::class);
    }
}
