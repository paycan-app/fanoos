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

    #[Reactive]
    public ?string $currencySymbol = '$';

    #[Reactive]
    public ?string $currencyCode = 'USD';

    protected function getData(): array
    {
        if (empty($this->segmentStats) || ! is_array($this->segmentStats)) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $revenueData = collect($this->segmentStats)
            ->filter(fn ($segment) => isset($segment['segment'], $segment['customers'], $segment['avg_monetary']))
            ->map(function ($segment) {
                $customers = (int) ($segment['customers'] ?? 0);
                $avgMonetary = (float) ($segment['avg_monetary'] ?? 0);

                return [
                    'segment' => $segment['segment'],
                    'revenue' => $customers * $avgMonetary,
                ];
            })
            ->filter(fn ($item) => $item['revenue'] > 0)
            ->sortByDesc('revenue')
            ->values()
            ->all();

        if (empty($revenueData)) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $colors = $this->getSegmentColors();
        $currencyCode = strtoupper($this->currencyCode ?? 'USD');

        $labels = array_column($revenueData, 'segment');
        $revenueValues = array_column($revenueData, 'revenue');
        $backgroundColors = array_map(
            fn ($segment) => $colors[$segment] ?? 'rgba(107, 114, 128, 0.5)',
            $labels
        );
        $borderColors = array_map(
            fn ($color) => str_replace('0.5', '1', $color),
            $backgroundColors
        );

        return [
            'datasets' => [
                [
                    'label' => "Revenue ({$currencyCode})",
                    'data' => $revenueValues,
                    'backgroundColor' => $backgroundColors,
                    'borderColor' => $borderColors,
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
                    'display' => false,
                ],
            ],
            'scales' => [
                'x' => [
                    'ticks' => [
                        'maxRotation' => 45,
                        'minRotation' => 45,
                    ],
                ],
                'y' => [
                    'beginAtZero' => true,
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
            'Champions' => 'rgba(234, 179, 8, 0.5)',
            'Loyal Customers' => 'rgba(34, 197, 94, 0.5)',
            'Potential Loyalist' => 'rgba(59, 130, 246, 0.5)',
            'New Customers' => 'rgba(168, 85, 247, 0.5)',
            'Promising' => 'rgba(6, 182, 212, 0.5)',
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
