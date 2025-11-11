<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class RfmSegmentTable extends Widget
{
    protected string $view = 'filament.widgets.rfm-segment-table';

    public ?array $segmentStats = null;

    public ?array $segmentDefinitions = null;

    public function getTableData(): array
    {
        if (empty($this->segmentStats)) {
            return [];
        }

        return collect($this->segmentStats)->map(function ($segment) {
            $definition = $this->segmentDefinitions[$segment['segment']] ?? null;

            return [
                'segment' => $segment['segment'],
                'customers' => $segment['customers'],
                'avg_recency' => $segment['avg_recency'],
                'avg_frequency' => $segment['avg_frequency'],
                'avg_monetary' => $segment['avg_monetary'],
                'total_revenue' => $segment['customers'] * $segment['avg_monetary'],
                'description' => $definition['description'] ?? '',
                'business_action' => $definition['business_action'] ?? '',
                'color' => $definition['color'] ?? 'gray',
            ];
        })
            ->sortByDesc('total_revenue')
            ->values()
            ->all();
    }
}
