<?php

namespace App\Filament\Pages;

use App\Models\Product;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use UnitEnum;

class CrossSellAnalysis extends Page
{
    protected static ?string $navigationLabel = 'Cross-Sell Analysis';

    protected static ?string $title = 'Cross-Sell Analysis';

    protected static UnitEnum|string|null $navigationGroup = 'Analytics';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ChartBarSquare;

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.cross-sell-analysis';

    public array $productCounts = [];

    public array $productLabels = [];

    public function mount(): void
    {
        $orderItems = DB::table('order_items')
            ->select('order_id', 'product_id')
            ->get();

        // Group order items by order_id
        $ordersWithProducts = $orderItems->groupBy('order_id');

        // Extract product pairs from each order
        $productPairs = [];

        foreach ($ordersWithProducts as $orderId => $items) {
            $productIds = $items->pluck('product_id')->unique()->sort()->values();

            // Create pairs from products in the same order
            // Only create pairs if there are at least 2 products
            if ($productIds->count() >= 2) {
                for ($i = 0; $i < $productIds->count(); $i++) {
                    for ($j = $i + 1; $j < $productIds->count(); $j++) {
                        // Create a consistent pair key (always sorted)
                        $pairKey = $productIds[$i].'-'.$productIds[$j];
                        $productPairs[] = $pairKey;
                    }
                }
            }
        }

        // Count occurrences of each pair
        $pairCounts = array_count_values($productPairs);

        // Sort by count (descending) and get top 10
        arsort($pairCounts);
        $topPairs = array_slice($pairCounts, 0, 20, true);

        // Get product details for labels
        $allProductIds = collect($topPairs)->keys()
            ->flatMap(fn ($pairKey) => explode('-', $pairKey))
            ->unique();

        $products = Product::whereIn('id', $allProductIds)
            ->get()
            ->keyBy('id');

        // Build labels and values arrays
        $labels = [];
        $values = [];

        foreach ($topPairs as $pairKey => $count) {
            [$productId1, $productId2] = explode('-', $pairKey);

            $product1 = $products->get($productId1);
            $product2 = $products->get($productId2);

            $product1Name = $product1?->title ?? "Product {$productId1}";
            $product2Name = $product2?->title ?? "Product {$productId2}";

            $labels[] = "{$product1Name} & {$product2Name}";
            $values[] = $count;
        }

        $this->productCounts = array_combine($labels, $values) ?: [];
        $this->productLabels = $labels;
    }
}
