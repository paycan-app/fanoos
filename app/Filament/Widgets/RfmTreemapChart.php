<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Livewire\Attributes\Reactive;

class RfmTreemapChart extends Widget
{
    protected string $view = 'filament.widgets.rfm-treemap-chart';

    protected int|string|array $columnSpan = 'full';

    #[Reactive]
    public ?array $segmentStats = null;

    #[Reactive]
    public ?array $segmentDefinitions = null;

    public function getTreemapData(): array
    {
        if (empty($this->segmentStats)) {
            return [];
        }

        $colors = $this->getSegmentColors();
        $data = [];

        foreach ($this->segmentStats as $stat) {
            $segment = $stat['segment'];
            $customers = $stat['customers'];
            $totalRevenue = $stat['avg_monetary'] * $customers;

            $data[] = [
                'x' => $segment,
                'y' => $totalRevenue,
                'customers' => $customers,
                'avgMonetary' => number_format($stat['avg_monetary'], 2),
                'avgFrequency' => number_format($stat['avg_frequency'], 1),
                'avgRecency' => number_format($stat['avg_recency'], 0),
                'fillColor' => $colors[$segment] ?? '#6B7280',
            ];
        }

        return $data;
    }

    protected function getSegmentColors(): array
    {
        return [
            'Champions' => '#EAB308', // yellow
            'Loyal Customers' => '#22C55E', // green
            'Potential Loyalist' => '#3B82F6', // blue
            'New Customers' => '#A855F7', // purple
            'Promising' => '#06B6D4', // cyan
            'Customers Needing Attention' => '#F97316', // orange
            'Need Attention' => '#F97316', // orange
            'About To Sleep' => '#F59E0B', // amber
            'At Risk' => '#FB923C', // orange-light
            'Cannot Lose Them' => '#DC2626', // red
            'Hibernating' => '#991B1B', // red-dark
            'Lost' => '#7F1D1D', // red-darker
            'High Value' => '#22C55E', // green
            'Medium Value' => '#3B82F6', // blue
            'Low Value' => '#F59E0B', // amber
        ];
    }

    public function getSegmentDescription(string $segment): string
    {
        if (empty($this->segmentDefinitions) || ! isset($this->segmentDefinitions[$segment])) {
            return '';
        }

        $def = $this->segmentDefinitions[$segment];

        return $def['description'] ?? '';
    }
}
