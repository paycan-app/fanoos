<?php

use App\Models\User;
use App\Models\Customer;
use Filament\Facades\Filament;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('authenticated admin can view customer record', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create();

    $this->actingAs($user);

    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $response = $this->get("/admin/customers/{$customer->id}");

    $response->assertSuccessful();
    $response->assertSee($customer->first_name);
});

test('guest cannot view customer record', function () {
    $customer = Customer::factory()->create();

    $response = $this->get("/admin/customers/{$customer->id}");

    $response->assertRedirect('/admin/login');
});
