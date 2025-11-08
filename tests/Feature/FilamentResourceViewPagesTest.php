<?php

use App\Models\User;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\OrderItem;
use Filament\Facades\Filament;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('authenticated admin can view order record', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create();

    $this->actingAs($user);

    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $response = $this->get("/admin/orders/{$order->id}");

    $response->assertSuccessful();
});

test('authenticated admin can view product record', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();

    $this->actingAs($user);

    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $response = $this->get("/admin/products/{$product->id}");

    $response->assertSuccessful();
    $response->assertSee($product->title);
});

test('authenticated admin can view order item record', function () {
    $user = User::factory()->create();
    $orderItem = OrderItem::factory()->create();

    $this->actingAs($user);

    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $response = $this->get("/admin/order-items/{$orderItem->id}");

    $response->assertSuccessful();
});
