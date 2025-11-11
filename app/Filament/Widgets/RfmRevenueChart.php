<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Livewire\Attributes\Reactive;

class RfmRevenueChart extends ChartWidget
{
    protected ?string $heading = 'Revenue by Segment';

    protected ?string $description = 'Total revenue contribution from each segment';

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

        // Calculate revenue and sort
        $revenueData = collect($this->segmentStats)
            ->map(fn ($segment) => [
                'segment' => $segment['segment'],
                'revenue' => $segment['customers'] * $segment['avg_monetary'],
            ])
            ->sortByDesc('revenue')
            ->values()
            ->all();

        $colors = $this->getSegmentColors();

        return [
            'datasets' => [
                [
                    'label' => 'Revenue ($)',
                    'data' => array_column($revenueData, 'revenue'),
                    'backgroundColor' => array_map(
                        fn ($item) => $colors[$item['segment']] ?? 'rgba(107, 114, 128, 0.5)',
                        $revenueData
                    ),
                    'borderColor' => array_map(
                        fn ($item) => str_replace('0.5', '1', $colors[$item['segment']] ?? 'rgba(107, 114, 128, 1)'),
                        $revenueData
                    ),
                    'borderWidth' => 2,
                ],
            ],
            'labels' => array_column($revenueData, 'segment'),
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
                'tooltip' => [
                    'callbacks' => [
                        'label' => new \Filament\Support\RawJs('function(context) { return "$" + context.parsed.y.toLocaleString(); }'),
                    ],
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => new \Filament\Support\RawJs('function(value) { return "$" + value.toLocaleString(); }'),
                    ],
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
        ];
    }

    protected function getSegmentColors(): array
    {
        return [
            'Champions' => 'rgba(234, 179, 8, 0.5)',
            'Loyal Customers' => 'rgba(34, 197, 94, 0.5)',
            'Potential Loyalist' => 'rgba(59, 130, 246, 0.5)',
            'New Customers' => 'rgba(168, 85, 247, 0.5)',
            'Promising' => 'rgba(6, 182, 212, 0.5)',
            'Customers Needing Attention' => 'rgba(249, 115, 22, 0.5)',
            'Need Attention' => 'rgba(249, 115, 22, 0.5)',
            'About To Sleep' => 'rgba(245, 158, 11, 0.5)',
            'At Risk' => 'rgba(251, 146, 60, 0.5)',
            'Cannot Lose Them' => 'rgba(220, 38, 38, 0.5)',
            'Hibernating' => 'rgba(153, 27, 27, 0.5)',
            'Lost' => 'rgba(127, 29, 29, 0.5)',
            'High Value' => 'rgba(34, 197, 94, 0.5)',
            'Medium Value' => 'rgba(59, 130, 246, 0.5)',
            'Low Value' => 'rgba(245, 158, 11, 0.5)',
        ];
    }
}
