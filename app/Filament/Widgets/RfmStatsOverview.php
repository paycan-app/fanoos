<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RfmStatsOverview extends StatsOverviewWidget
{
    public ?array $segmentStats = null;

    public ?array $previousSegmentStats = null;

    protected function getStats(): array
    {
        if (empty($this->segmentStats)) {
            return [];
        }

        $totalCustomers = collect($this->segmentStats)->sum('customers');
        $totalRevenue = collect($this->segmentStats)->sum(fn ($s) => $s['customers'] * $s['avg_monetary']);
        $avgMonetary = $totalCustomers > 0 ? $totalRevenue / $totalCustomers : 0;
        $activeSegments = count($this->segmentStats);

        // Calculate trends if previous data exists
        $customerTrend = 0;
        $revenueTrend = 0;
        $avgValueTrend = 0;

        if (! empty($this->previousSegmentStats)) {
            $prevTotalCustomers = collect($this->previousSegmentStats)->sum('customers');
            $prevTotalRevenue = collect($this->previousSegmentStats)->sum(fn ($s) => $s['customers'] * $s['avg_monetary']);
            $prevAvgMonetary = $prevTotalCustomers > 0 ? $prevTotalRevenue / $prevTotalCustomers : 0;

            $customerTrend = $prevTotalCustomers > 0 ? (($totalCustomers - $prevTotalCustomers) / $prevTotalCustomers) * 100 : 0;
            $revenueTrend = $prevTotalRevenue > 0 ? (($totalRevenue - $prevTotalRevenue) / $prevTotalRevenue) * 100 : 0;
            $avgValueTrend = $prevAvgMonetary > 0 ? (($avgMonetary - $prevAvgMonetary) / $prevAvgMonetary) * 100 : 0;
        }

        return [
            Stat::make('Total Revenue', '$'.number_format($totalRevenue, 0))
                ->description($this->getTrendDescription($revenueTrend))
                ->descriptionIcon($revenueTrend >= 0 ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down')
                ->descriptionColor($revenueTrend >= 0 ? 'success' : 'danger')
                ->color('info')
                ->icon('heroicon-o-currency-dollar'),

            Stat::make('Total Customers', number_format($totalCustomers))
                ->description($this->getTrendDescription($customerTrend))
                ->descriptionIcon($customerTrend >= 0 ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down')
                ->descriptionColor($customerTrend >= 0 ? 'success' : 'danger')
                ->color('success')
                ->icon('heroicon-o-user-group'),

            Stat::make('Avg Customer Value', '$'.number_format($avgMonetary, 2))
                ->description($this->getTrendDescription($avgValueTrend))
                ->descriptionIcon($avgValueTrend >= 0 ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down')
                ->descriptionColor($avgValueTrend >= 0 ? 'success' : 'danger')
                ->color('warning')
                ->icon('heroicon-o-chart-bar'),

            Stat::make('Active Segments', $activeSegments)
                ->description('Customer segments')
                ->color('primary')
                ->icon('heroicon-o-tag'),
        ];
    }

    protected function getTrendDescription(float $trend): string
    {
        if ($trend == 0) {
            return 'No change';
        }

        $formatted = number_format(abs($trend), 1);

        return $trend > 0 ? "↗ {$formatted}% increase" : "↘ {$formatted}% decrease";
    }
}
