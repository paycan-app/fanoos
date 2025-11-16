<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\InteractsWithDateFilters;
use App\Services\DashboardAnalyticsService;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;

class TopProductsTable extends TableWidget
{
    use InteractsWithDateFilters;
    use InteractsWithPageFilters;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = ['@2xl' => 1, '!@lg' => 2];

    protected ?string $pollingInterval = '3m';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Top 10 products')
            ->description('Share of revenue in the selected period.')
            ->paginated(false)
            ->records(fn (): Collection => $this->getRecords())
            ->columns([
                TextColumn::make('rank')
                    ->label('#')
                    ->alignCenter()
                    ->formatStateUsing(fn (array $record): string => (string) $record['rank']),
                TextColumn::make('title')
                    ->label('Product')
                    ->description(fn (array $record): ?string => $record['category'] ?: null)
                    ->limit(32)
                    ->wrap(),
                TextColumn::make('revenue')
                    ->label('Revenue')
                    ->alignRight()
                    ->state(fn (array $record): string => Number::currency($record['revenue'], $this->getCurrencyCode())),
                TextColumn::make('quantity')
                    ->label('Units')
                    ->alignRight()
                    ->state(fn (array $record): string => Number::format($record['quantity'])),
                TextColumn::make('share')
                    ->label('Mix')
                    ->alignRight()
                    ->state(fn (array $record): string => Number::format($record['share'], 1).' %'),
            ])
            ->emptyStateHeading('No orders yet')
            ->emptyStateDescription('Import orders or adjust the date range to see product leaders.');
    }

    protected function getRecords(): Collection
    {
        $range = $this->resolvePrimaryRange();
        $analytics = $this->analytics();
        $summary = $analytics->summarize($range);
        $totalRevenue = max($summary['revenue'], 1);

        return $analytics->topProducts($range)
            ->values()
            ->map(function (array $product, int $index) use ($totalRevenue): array {
                $share = round(($product['revenue'] / $totalRevenue) * 100, 1);

                return [
                    'id' => $product['product_id'],
                    'rank' => $index + 1,
                    'title' => $product['title'] ?? 'Untitled',
                    'category' => $product['category'] ?? null,
                    'revenue' => $product['revenue'],
                    'quantity' => $product['quantity'],
                    'share' => $share,
                ];
            });
    }

    protected function analytics(): DashboardAnalyticsService
    {
        return app(DashboardAnalyticsService::class);
    }

    protected function getCurrencyCode(): string
    {
        return config('app.currency', 'USD');
    }
}
