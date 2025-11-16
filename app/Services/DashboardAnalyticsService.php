<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class DashboardAnalyticsService
{
    /**
     * @param  array{start: mixed, end: mixed}  $range
     * @return array{
     *     revenue: float,
     *     orders: int,
     *     customers: int,
     *     avg_order_value: float,
     *     new_customers: int,
     *     new_orders: int,
     *     avg_items_per_order: float
     * }
     */
    public function summarize(array $range): array
    {
        $range = $this->normaliseRange($range);

        return Cache::remember(
            $this->cacheKey('summary', $range),
            now()->addMinutes(5),
            fn (): array => $this->buildSummary($range),
        );
    }

    /**
     * @param  array{start: mixed, end: mixed}  $range
     * @return array{
     *     labels: array<int, string>,
     *     revenue: array<int, float>,
     *     orders: array<int, int>,
     *     new_customers: array<int, int>
     * }
     */
    public function revenueOrdersTrend(array $range): array
    {
        $range = $this->normaliseRange($range);

        return Cache::remember(
            $this->cacheKey('trend', $range),
            now()->addMinutes(5),
            fn (): array => $this->buildTrend($range),
        );
    }

    /**
     * @param  array{start: mixed, end: mixed}  $range
     */
    public function topProducts(array $range, int $limit = 10): Collection
    {
        $range = $this->normaliseRange($range);

        return Cache::remember(
            $this->cacheKey("top-products-{$limit}", $range),
            now()->addMinutes(5),
            fn (): Collection => $this->buildTopProducts($range, $limit),
        );
    }

    /**
     * @param  array{start: CarbonImmutable, end: CarbonImmutable}  $range
     */
    protected function buildSummary(array $range): array
    {
        $ordersQuery = Order::query();
        $this->applyRange($ordersQuery, $range);

        $totalOrders = (clone $ordersQuery)->count();
        $totalRevenue = (float) (clone $ordersQuery)->sum('total_amount');
        $activeCustomers = (clone $ordersQuery)->distinct('customer_id')->count('customer_id');

        $newCustomers = Customer::query()
            ->whereBetween('created_at', [$range['start'], $range['end']])
            ->count();

        $newOrders = Order::query()
            ->whereBetween('created_at', [$range['start'], $range['end']])
            ->whereIn('customer_id', function ($query) use ($range): void {
                $query
                    ->select('customers.id')
                    ->from('customers')
                    ->whereBetween('customers.created_at', [$range['start'], $range['end']]);
            })
            ->count();

        $totalItems = OrderItem::query()
            ->whereHas('order', fn ($query) => $this->applyRange($query, $range))
            ->sum('quantity');

        $avgOrderValue = $totalOrders > 0 ? round($totalRevenue / $totalOrders, 2) : 0.0;
        $avgItemsPerOrder = $totalOrders > 0 ? round($totalItems / $totalOrders, 2) : 0.0;

        return [
            'revenue' => round($totalRevenue, 2),
            'orders' => $totalOrders,
            'customers' => $activeCustomers,
            'avg_order_value' => $avgOrderValue,
            'new_customers' => $newCustomers,
            'new_orders' => $newOrders,
            'avg_items_per_order' => $avgItemsPerOrder,
        ];
    }

    /**
     * @param  array{start: CarbonImmutable, end: CarbonImmutable}  $range
     */
    protected function buildTrend(array $range): array
    {
        $orders = Order::query()
            ->selectRaw('DATE(created_at) as bucket')
            ->selectRaw('SUM(total_amount) as revenue')
            ->selectRaw('COUNT(*) as total_orders')
            ->whereBetween('created_at', [$range['start'], $range['end']])
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get()
            ->keyBy('bucket');

        $newCustomers = Customer::query()
            ->selectRaw('DATE(created_at) as bucket')
            ->selectRaw('COUNT(*) as total_new')
            ->whereBetween('created_at', [$range['start'], $range['end']])
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get()
            ->keyBy('bucket');

        $period = CarbonPeriod::create($range['start'], '1 day', $range['end']);

        $labels = [];
        $revenuePoints = [];
        $orderPoints = [];
        $newCustomerPoints = [];

        foreach ($period as $date) {
            $key = $date->format('Y-m-d');
            $labels[] = $date->format('M j');
            $revenuePoints[] = (float) ($orders[$key]->revenue ?? 0);
            $orderPoints[] = (int) ($orders[$key]->total_orders ?? 0);
            $newCustomerPoints[] = (int) ($newCustomers[$key]->total_new ?? 0);
        }

        return [
            'labels' => $labels,
            'revenue' => $revenuePoints,
            'orders' => $orderPoints,
            'new_customers' => $newCustomerPoints,
        ];
    }

    /**
     * @param  array{start: CarbonImmutable, end: CarbonImmutable}  $range
     */
    protected function buildTopProducts(array $range, int $limit): Collection
    {
        $rows = OrderItem::query()
            ->selectRaw('products.id as product_id')
            ->selectRaw('products.title as product_title')
            ->selectRaw('products.category as product_category')
            ->selectRaw('SUM(order_items.quantity) as total_quantity')
            ->selectRaw('SUM(order_items.price) as total_revenue')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereBetween('orders.created_at', [$range['start'], $range['end']])
            ->groupBy('products.id', 'products.title', 'products.category')
            ->orderByDesc('total_revenue')
            ->limit($limit)
            ->get();

        return $rows
            ->map(fn ($row): array => [
                'product_id' => $row->product_id,
                'title' => $row->product_title,
                'category' => $row->product_category,
                'quantity' => (int) $row->total_quantity,
                'revenue' => (float) $row->total_revenue,
                'avg_price' => $row->total_quantity > 0
                    ? round($row->total_revenue / $row->total_quantity, 2)
                    : 0.0,
            ]);
    }

    /**
     * @param  array{start: mixed, end: mixed}  $range
     * @return array{start: CarbonImmutable, end: CarbonImmutable}
     */
    protected function normaliseRange(array $range): array
    {
        $start = $range['start'] ?? now()->subDays(29)->startOfDay();
        $end = $range['end'] ?? now()->endOfDay();

        if (! $start instanceof CarbonImmutable) {
            $start = CarbonImmutable::parse($start)->startOfDay();
        }

        if (! $end instanceof CarbonImmutable) {
            $end = CarbonImmutable::parse($end)->endOfDay();
        }

        if ($start->greaterThan($end)) {
            [$start, $end] = [$end->subDays(29)->startOfDay(), $end];
        }

        return [
            'start' => $start,
            'end' => $end,
        ];
    }

    /**
     * @param  array{start: CarbonImmutable, end: CarbonImmutable}  $range
     */
    protected function applyRange(Builder|QueryBuilder $query, array $range, string $column = 'created_at'): Builder|QueryBuilder
    {
        return $query->whereBetween($column, [$range['start'], $range['end']]);
    }

    /**
     * @param  array{start: CarbonImmutable, end: CarbonImmutable}  $range
     */
    protected function cacheKey(string $prefix, array $range): string
    {
        return sprintf(
            '%s:%s:%s',
            $prefix,
            $range['start']->format('Ymd'),
            $range['end']->format('Ymd'),
        );
    }
}
