<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\InteractsWithDateFilters;
use App\Services\DashboardAnalyticsService;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class BusinessPulseStats extends StatsOverviewWidget
{
    use InteractsWithDateFilters;
    use InteractsWithPageFilters;

    protected static bool $isLazy = false;

    protected ?string $heading = 'Business pulse snapshot';

    protected ?string $description = 'Compare revenue, orders, and growth against the selected benchmark.';

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = '120s';

    protected function getStats(): array
    {
        $range = $this->resolvePrimaryRange();
        $comparisonRange = $this->resolveComparisonRange($range);

        $analytics = $this->analytics();
        $current = $analytics->summarize($range);
        $previous = $comparisonRange ? $analytics->summarize($comparisonRange) : null;

        return [
            $this->makeStat(
                label: 'Total Revenue',
                current: $current['revenue'],
                previous: $previous['revenue'] ?? null,
                isCurrency: true,
                icon: Heroicon::CurrencyDollar,
            ),
            $this->makeStat(
                label: 'Total Orders',
                current: $current['orders'],
                previous: $previous['orders'] ?? null,
                icon: Heroicon::ReceiptPercent,
            ),
            $this->makeStat(
                label: 'Total Customers',
                current: $current['total_customers'],
                previous: $previous['total_customers'] ?? null,
                icon: Heroicon::UserGroup,
            ),
            $this->makeStat(
                label: 'Active Customers',
                current: $current['customers'],
                previous: $previous['customers'] ?? null,
                icon: Heroicon::UserGroup,
            ),
            $this->makeStat(
                label: 'Average Order Value',
                current: $current['avg_order_value'],
                previous: $previous['avg_order_value'] ?? null,
                isCurrency: true,
                icon: Heroicon::PresentationChartLine,
                decimals: 2,
            ),
            $this->makeStat(
                label: 'New Customers',
                current: $current['new_customers'],
                previous: $previous['new_customers'] ?? null,
                icon: Heroicon::UserPlus,
            ),
            $this->makeStat(
                label: 'New Orders',
                current: $current['new_orders'],
                previous: $previous['new_orders'] ?? null,
                icon: Heroicon::InboxStack,
            ),
            $this->makeStat(
                label: 'Avg. Items / Order',
                current: $current['avg_items_per_order'],
                previous: $previous['avg_items_per_order'] ?? null,
                icon: Heroicon::Squares2x2,
                decimals: 2,
            ),
        ];
    }

    protected function makeStat(
        string $label,
        float|int $current,
        ?float $previous = null,
        bool $isCurrency = false,
        string|BackedEnum $icon = Heroicon::ChartBar,
        ?int $decimals = null,
    ): Stat {
        $stat = Stat::make($label, $this->formatValue($current, $isCurrency, $decimals))
            ->icon($icon);

        [$color, $accentClass] = $this->statStyling($label);

        if ($color) {
            $stat->color($color);
        }

        if ($accentClass) {
            $stat->extraAttributes([
                'class' => $accentClass,
            ]);
        }

        if ($previous === null) {
            return $stat->description('Waiting for baseline');
        }

        [$description, $directionIcon, $color] = $this->describeChange($current, $previous, $isCurrency, $decimals);

        return $stat
            ->description($description)
            ->descriptionIcon($directionIcon)
            ->descriptionColor($color);
    }

    /**
     * @return array{string, string, string}
     */
    protected function describeChange(
        float|int $current,
        float|int $previous,
        bool $isCurrency,
        ?int $decimals = null,
    ): array {
        if (abs($current - $previous) < 0.0001) {
            return ['No change vs '.$this->comparisonLabel(), Heroicon::ArrowsRightLeft, 'gray'];
        }

        $delta = $current - $previous;
        $directionIcon = $delta >= 0 ? Heroicon::ArrowTrendingUp : Heroicon::ArrowTrendingDown;
        $color = $delta >= 0 ? 'success' : 'danger';

        $deltaValue = $this->formatValue(abs($delta), $isCurrency, $decimals);
        $percent = $previous != 0 ? round(($delta / $previous) * 100, 1) : null;

        $description = sprintf(
            '%s%s%s vs %s',
            $delta >= 0 ? '+' : '-',
            $deltaValue,
            $percent !== null ? sprintf(' (%s%s%%)', $delta >= 0 ? '+' : '-', Number::format(abs($percent), 1)) : '',
            $this->comparisonLabel(),
        );

        return [$description, $directionIcon, $color];
    }

    protected function comparisonLabel(): string
    {
        return match ($this->pageFilters['comparison_mode'] ?? 'previous_period') {
            'previous_year' => 'previous year',
            'custom' => 'comparison range',
            default => 'previous period',
        };
    }

    protected function formatValue(float|int $value, bool $isCurrency = false, ?int $decimals = null): string
    {
        if ($isCurrency) {
            return Number::currency($value, $this->getCurrencyCode());
        }

        if ($decimals !== null) {
            return Number::format($value, $decimals);
        }

        $precision = abs($value) >= 10 ? 0 : 1;

        return Number::format($value, $precision);
    }

    /**
     * @return array{string|null, string|null}
     */
    protected function statStyling(string $label): array
    {
        return match ($label) {
            'Total Revenue' => ['primary', 'bg-gradient-to-br from-amber-50 to-amber-100 dark:from-amber-500 dark:to-amber-600 dark:text-gray-300 dark:shadow-lg dark:shadow-amber-500/20'],
            'Total Orders' => ['info', 'bg-gradient-to-br from-sky-50 to-sky-100 dark:from-sky-500 dark:to-sky-600 dark:text-gray-300 dark:shadow-lg dark:shadow-sky-500/20'],
            'Total Customers' => ['gray', 'bg-gradient-to-br from-violet-50 to-violet-100 dark:from-violet-500 dark:to-violet-600 dark:text-gray-300 dark:shadow-lg dark:shadow-violet-500/20'],
            'Active Customers' => ['success', 'bg-gradient-to-br from-emerald-50 to-emerald-100 dark:from-emerald-500 dark:to-emerald-600 dark:text-gray-300 dark:shadow-lg dark:shadow-emerald-500/20'],
            'Average Order Value' => ['warning', 'bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-500 dark:to-orange-600 dark:text-gray-300 dark:shadow-lg dark:shadow-orange-500/20'],
            'New Customers' => ['success', 'bg-gradient-to-br from-green-50 to-green-100 dark:from-green-500 dark:to-green-600 dark:text-gray-300 dark:shadow-lg dark:shadow-green-500/20'],
            'New Orders' => ['primary', 'bg-gradient-to-br from-indigo-50 to-indigo-100 dark:from-indigo-500 dark:to-indigo-600 dark:text-gray-300 dark:shadow-lg dark:shadow-indigo-500/20'],
            'Avg. Items / Order' => ['gray', 'bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-500 dark:to-slate-600 dark:text-gray-300 dark:shadow-lg dark:shadow-slate-500/20'],
            default => [null, null],
        };
    }

    protected function getColumns(): int|array
    {
        return [
            '@lg' => 4,
            '!@lg' => 2,
        ];
    }

    protected function getCurrencyCode(): string
    {
        return config('app.currency', 'USD');
    }

    protected function analytics(): DashboardAnalyticsService
    {
        return app(DashboardAnalyticsService::class);
    }
}
