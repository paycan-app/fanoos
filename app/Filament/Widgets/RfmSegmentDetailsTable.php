<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Livewire\Attributes\Reactive;

class RfmSegmentDetailsTable extends Widget
{
    protected string $view = 'filament.widgets.rfm-segment-details-table';

    protected int|string|array $columnSpan = 'full';

    #[Reactive]
    public ?array $segmentStats = null;

    #[Reactive]
    public ?array $segmentDefinitions = null;

    #[Reactive]
    public ?string $currencySymbol = '$';

    public function getTableData(): array
    {
        if (empty($this->segmentStats)) {
            return [];
        }

        $totalCustomers = collect($this->segmentStats)->sum('customers');

        return collect($this->segmentStats)
            ->map(function ($segment) use ($totalCustomers) {
                $definition = $this->segmentDefinitions[$segment['segment']] ?? null;

                return [
                    'segment' => $segment['segment'],
                    'customers' => $segment['customers'],
                    'percentage' => $totalCustomers > 0 ? round(($segment['customers'] / $totalCustomers) * 100, 2) : 0,
                    'avg_recency' => $segment['avg_recency'],
                    'avg_frequency' => $segment['avg_frequency'],
                    'avg_monetary' => $segment['avg_monetary'],
                    'total_revenue' => $segment['customers'] * $segment['avg_monetary'],
                    'description' => $definition['description'] ?? '',
                    'business_action' => $definition['business_action'] ?? '',
                    'color' => $this->getSegmentBadgeColor($segment['segment']),
                ];
            })
            ->sortByDesc('total_revenue')
            ->values()
            ->toArray();
    }

    protected function getSegmentBadgeColor(string $segment): string
    {
        return match ($segment) {
            'Champions' => 'warning',
            'Loyal Customers' => 'success',
            'Potential Loyalist' => 'primary',
            'New Customers' => 'purple',
            'Promising' => 'info',
            'Need Attention' => 'warning',
            'About To Sleep' => 'warning',
            'At Risk' => 'danger',
            'Cannot Lose Them' => 'danger',
            'Hibernating' => 'danger',
            'Lost' => 'danger',
            'High Value' => 'success',
            'Medium Value' => 'primary',
            'Low Value' => 'warning',
            default => 'gray',
        };
    }
}
