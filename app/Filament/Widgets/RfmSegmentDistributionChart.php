<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Livewire\Attributes\Reactive;

class RfmSegmentDistributionChart extends ChartWidget
{
    protected ?string $heading = 'Customer Segments Distribution';

    protected ?string $description = 'Number of customers in each segment';

    #[Reactive]
    public ?array $segmentStats = null;

    protected function getData(): array
    {
        if (empty($this->segmentStats)) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        // Sort by customer count for better visualization
        $sorted = collect($this->segmentStats)->sortByDesc('customers')->values()->all();

        $colors = $this->getSegmentColors();

        return [
            'datasets' => [
                [
                    'label' => 'Customers',
                    'data' => array_column($sorted, 'customers'),
                    'backgroundColor' => array_map(
                        fn ($segment) => $colors[$segment['segment']] ?? 'rgba(107, 114, 128, 0.5)',
                        $sorted
                    ),
                    'borderColor' => array_map(
                        fn ($segment) => str_replace('0.5', '1', $colors[$segment['segment']] ?? 'rgba(107, 114, 128, 1)'),
                        $sorted
                    ),
                    'borderWidth' => 2,
                ],
            ],
            'labels' => array_column($sorted, 'segment'),
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
                    'display' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
            'indexAxis' => 'x',
            'responsive' => true,
            'maintainAspectRatio' => false,
        ];
    }

    protected function getSegmentColors(): array
    {
        return [
            'Champions' => 'rgba(234, 179, 8, 0.5)', // yellow
            'Loyal Customers' => 'rgba(34, 197, 94, 0.5)', // green
            'Potential Loyalist' => 'rgba(59, 130, 246, 0.5)', // blue
            'New Customers' => 'rgba(168, 85, 247, 0.5)', // purple
            'Promising' => 'rgba(6, 182, 212, 0.5)', // cyan
            'Need Attention' => 'rgba(249, 115, 22, 0.5)', // orange
            'About To Sleep' => 'rgba(245, 158, 11, 0.5)', // amber
            'At Risk' => 'rgba(251, 146, 60, 0.5)', // orange-light
            'Cannot Lose Them' => 'rgba(220, 38, 38, 0.5)', // red
            'Hibernating' => 'rgba(153, 27, 27, 0.5)', // red-dark
            'Lost' => 'rgba(127, 29, 29, 0.5)', // red-darker
            'High Value' => 'rgba(34, 197, 94, 0.5)', // green
            'Medium Value' => 'rgba(59, 130, 246, 0.5)', // blue
            'Low Value' => 'rgba(245, 158, 11, 0.5)', // amber
        ];
    }
}
