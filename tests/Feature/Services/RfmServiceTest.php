<?php

use App\Models\Customer;
use App\Models\Order;
use App\Services\RfmService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FakeRfmSettings;

uses(RefreshDatabase::class);

function makeRfmService(array $overrides = []): RfmService
{
    $config = array_merge([
        'rfm_bins' => 5,
        'rfm_segments' => 5,
        'rfm_timeframe_days' => 120,
    ], $overrides);

    $settings = new FakeRfmSettings(
        bins: $config['rfm_bins'],
        segments: $config['rfm_segments'],
        timeframeDays: $config['rfm_timeframe_days'],
    );

    return new RfmService($settings);
}

it('returns a friendly message when there are no customers', function (): void {
    $service = makeRfmService();

    $stats = $service->calculateSegments();

    expect($stats)->toHaveKey('message')
        ->and($stats['message'])->toContain('No customers');
});

it('returns a message when no orders exist in the timeframe', function (): void {
    $customer = Customer::factory()->create();

    Order::factory()->create([
        'customer_id' => $customer->id,
        'created_at' => now()->subDays(400),
        'total_amount' => 250,
    ]);

    $service = makeRfmService();
    $stats = $service->calculateSegments(timeframeDays: 60, asOfDate: now());

    expect($stats)->toHaveKey('message')
        ->and($stats['message'])->toContain('No order activity');
});

it('summarizes segment stats with totals and high value share', function (): void {
    $recentCustomer = Customer::factory()->create();
    $loyalCustomer = Customer::factory()->create();

    Order::factory()->create([
        'customer_id' => $recentCustomer->id,
        'created_at' => now()->subDays(5),
        'total_amount' => 500,
    ]);

    Order::factory()->count(3)->create([
        'customer_id' => $loyalCustomer->id,
        'created_at' => now()->subDays(10),
        'total_amount' => 750,
    ]);

    $service = makeRfmService(['rfm_timeframe_days' => 180]);
    $stats = $service->calculateSegments(timeframeDays: 120, asOfDate: now());

    expect($stats)->not->toHaveKey('message')
        ->and($stats)->not->toBeEmpty();

    $summary = $service->summarizeSegments($stats, 'EUR');

    expect($summary['has_data'])->toBeTrue()
        ->and($summary['currency'])->toBe('EUR')
        ->and($summary['total_customers'])->toBe(collect($stats)->sum('customers'))
        ->and($summary['total_revenue']['value'])->toBeGreaterThan(0)
        ->and($summary['average_value']['value'])->toBeGreaterThan(0)
        ->and($summary['high_value_share'])->toBeGreaterThanOrEqual(0)
        ->and($summary['high_value_share'])->toBeLessThanOrEqual(100)
        ->and($summary['top_segments'])->not->toBeEmpty();
});

it('builds segment snapshots for specific comparison dates', function (): void {
    $customerA = Customer::factory()->create();
    $customerB = Customer::factory()->create();

    Order::factory()->create([
        'customer_id' => $customerA->id,
        'created_at' => now()->subDays(20),
        'total_amount' => 450,
    ]);

    Order::factory()->create([
        'customer_id' => $customerB->id,
        'created_at' => now()->subDays(50),
        'total_amount' => 275,
    ]);

    $service = makeRfmService(['rfm_timeframe_days' => 120]);
    $snapshot = $service->buildSegmentSnapshotForAsOfDate(now());

    expect($snapshot['as_of'])->toBe(now()->toDateString())
        ->and($snapshot['total_customers'])->toBe(2)
        ->and($snapshot['segments'])->not->toBeEmpty()
        ->and($snapshot['metrics']['total_monetary'])->toBeGreaterThan(0);
});
