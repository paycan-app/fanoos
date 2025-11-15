<?php

use App\Filament\Pages\SegmentTransitionComparison;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use App\Settings\RfmSettingsContract;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\Support\FakeRfmSettings;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config(['app.key' => 'base64:YWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWE=']);
    config(['app.env' => 'local']);

    Carbon::setTestNow('2025-11-15 00:00:00');

    $this->actingAs(User::factory()->create());
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $this->app->instance(RfmSettingsContract::class, new FakeRfmSettings(
        bins: 5,
        segments: 5,
        timeframeDays: 120,
    ));
});

afterEach(function (): void {
    Carbon::setTestNow();
});

it('renders the transition comparison page with insights and charts', function (): void {
    $champion = Customer::factory()->create();
    $atRisk = Customer::factory()->create();

    // Data for baseline (Period A ~ 1 year ago window)
    Order::factory()->create([
        'customer_id' => $champion->id,
        'created_at' => now()->subDays(400),
        'total_amount' => 980,
    ]);

    Order::factory()->create([
        'customer_id' => $atRisk->id,
        'created_at' => now()->subDays(420),
        'total_amount' => 320,
    ]);

    // Data for comparison period (recent window)
    Order::factory()->create([
        'customer_id' => $champion->id,
        'created_at' => now()->subDays(20),
        'total_amount' => 650,
    ]);

    $response = $this->get('/admin/segment-transition-comparison');

    $response->assertSuccessful();
    $response->assertSee('Change Highlights', false);
    $response->assertSee('Segment Sankey flow', false);
});

it('builds sankey payload after running comparison via Livewire', function (): void {
    $engaged = Customer::factory()->create();
    $churned = Customer::factory()->create();

    Order::factory()->create([
        'customer_id' => $engaged->id,
        'created_at' => now()->subDays(80),
        'total_amount' => 420,
    ]);

    Order::factory()->create([
        'customer_id' => $engaged->id,
        'created_at' => now()->subDays(10),
        'total_amount' => 510,
    ]);

    Order::factory()->create([
        'customer_id' => $churned->id,
        'created_at' => now()->subDays(140),
        'total_amount' => 300,
    ]);

    $component = Livewire::test(SegmentTransitionComparison::class)
        ->set('baselineDate', now()->subDays(45)->toDateString())
        ->set('comparisonDate', now()->subDays(5)->toDateString())
        ->call('generateComparison');

    $component->assertSet('message', null);

    $snapshotA = $component->get('snapshotA');
    $snapshotB = $component->get('snapshotB');
    $matrix = $component->get('transitionMatrix');
    $sankey = $component->get('sankeyData');

    expect($snapshotA['total_customers'] ?? 0)->toBeGreaterThan(0)
        ->and($snapshotB['total_customers'] ?? 0)->toBeGreaterThan(0)
        ->and($matrix['total'] ?? 0)->toBeGreaterThan(0)
        ->and($sankey['node']['labels'] ?? [])->not->toBeEmpty();
});
