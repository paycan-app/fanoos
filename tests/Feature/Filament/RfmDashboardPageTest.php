<?php

use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use App\Settings\RfmSettingsContract;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FakeRfmSettings;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config(['app.key' => 'base64:YWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWE=']);
    config(['app.env' => 'local']);

    $this->actingAs(User::factory()->create());
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $this->app->instance(RfmSettingsContract::class, new FakeRfmSettings(
        bins: 5,
        segments: 5,
        timeframeDays: 180,
    ));
});

it('renders the redesigned RFM dashboard with summary sections', function (): void {
    $champion = Customer::factory()->create();
    $loyal = Customer::factory()->create();

    Order::factory()->create([
        'customer_id' => $champion->id,
        'total_amount' => 1200,
        'created_at' => now()->subDays(5),
    ]);

    Order::factory()->count(3)->create([
        'customer_id' => $loyal->id,
        'total_amount' => 450,
        'created_at' => now()->subDays(15),
    ]);

    $response = $this->get('/admin/rfm-dashboard');

    $response->assertSuccessful();
    $response->assertDontSee('RFM data is not ready', false);
    $response->assertSee('Segment momentum', false);
    $response->assertSee('Win-back priority list', false);
    $response->assertSee('Metric cheat sheet', false);
});
