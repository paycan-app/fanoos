<?php

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config(['app.key' => 'base64:YWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWE=']);
    config(['app.env' => 'local']);

    Carbon::setTestNow('2025-11-15 00:00:00');
    Cache::flush();

    $this->actingAs(User::factory()->create());
    Filament::setCurrentPanel(Filament::getPanel('panel'));
});

afterEach(function (): void {
    Carbon::setTestNow();
});

test('dashboard widgets surface key business metrics', function (): void {
    $primaryProduct = Product::factory()->create([
        'title' => 'Signal Lamp',
        'category' => 'Lighting',
    ]);

    $recentCustomer = Customer::factory()->create([
        'created_at' => now()->subDays(10),
    ]);

    $recentOrder = Order::factory()->create([
        'customer_id' => $recentCustomer->id,
        'total_amount' => 420,
        'created_at' => now()->subDays(5),
        'status' => 'completed',
    ]);

    OrderItem::factory()->create([
        'order_id' => $recentOrder->id,
        'product_id' => $primaryProduct->id,
        'quantity' => 3,
        'unit_price' => 140,
        'price' => 420,
        'created_at' => now()->subDays(5),
    ]);

    // Baseline data for comparisons
    Order::factory()->create([
        'customer_id' => Customer::factory()->create()->id,
        'total_amount' => 215,
        'created_at' => now()->subDays(45),
        'status' => 'completed',
    ]);

    expect(Order::count())->toBe(2);
    expect(OrderItem::count())->toBe(1);

    $response = $this->get('/panel');

    $response->assertSuccessful();
    $response->assertSee('Business pulse snapshot', false);
    $response->assertSee('Revenue &amp; order trend', false);
    $response->assertSee('Top 10 products', false);
    $response->assertSee('Signal Lamp', false);
});
