<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Livewire\Attributes\Reactive;

class RfmMetricsChart extends ChartWidget
{
    protected ?string $heading = 'RFM Metrics Comparison';

    protected ?string $description = 'Average Recency, Frequency, and Monetary values by segment';

    #[Reactive]
    public ?array $segmentStats = null;

    #[Reactive]
    public ?string $currencySymbol = '$';

    protected function getData(): array
    {
        if (empty($this->segmentStats)) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $labels = array_column($this->segmentStats, 'segment');

        return [
            'datasets' => [
                [
                    'label' => 'Avg Monetary ('.($this->currencySymbol ?? '$').')',
                    'data' => array_column($this->segmentStats, 'avg_monetary'),
                    'backgroundColor' => 'rgba(34, 197, 94, 0.5)',
                    'borderColor' => 'rgba(34, 197, 94, 1)',
                    'borderWidth' => 2,
                ],
                [
                    'label' => 'Avg Frequency',
                    'data' => array_column($this->segmentStats, 'avg_frequency'),
                    'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                    'borderColor' => 'rgba(59, 130, 246, 1)',
                    'borderWidth' => 2,
                ],
                [
                    'label' => 'Avg Recency (days)',
                    'data' => array_column($this->segmentStats, 'avg_recency'),
                    'backgroundColor' => 'rgba(245, 158, 11, 0.5)',
                    'borderColor' => 'rgba(245, 158, 11, 1)',
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                ],
                'x' => [
                    'ticks' => [
                        'autoSkip' => false,
                        'maxRotation' => 45,
                        'minRotation' => 45,
                    ],
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
        ];
    }
}
