<?php

use App\Models\Campaign;
use App\Models\Customer;
use App\Models\User;
use App\Services\CampaignService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can create campaign with all customers', function () {
    $user = User::factory()->create();

    $campaign = Campaign::create([
        'name' => 'Test Campaign',
        'channel' => 'email',
        'status' => 'draft',
        'subject' => 'Test Subject',
        'content' => 'Hello {{first_name}}!',
        'filter_type' => 'all',
        'created_by' => $user->id,
    ]);

    expect($campaign)->toBeInstanceOf(Campaign::class)
        ->and($campaign->name)->toBe('Test Campaign')
        ->and($campaign->channel)->toBe('email');
});

test('can filter customers by segment', function () {
    Customer::factory()->count(10)->create(['segment' => 'Champions']);
    Customer::factory()->count(5)->create(['segment' => 'Lost']);

    $user = User::factory()->create();

    $campaign = Campaign::create([
        'name' => 'Segment Campaign',
        'channel' => 'email',
        'status' => 'draft',
        'subject' => 'Test',
        'content' => 'Test',
        'filter_type' => 'segment',
        'filter_config' => ['segments' => ['Champions']],
        'created_by' => $user->id,
    ]);

    $recipients = $campaign->getFilteredCustomers();

    expect($recipients)->toHaveCount(10);
});

test('replaces template variables correctly', function () {
    $campaignService = app(CampaignService::class);

    $customer = new Customer([
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
        'segment' => 'Champions',
    ]);

    $content = 'Hello {{first_name}} {{last_name}}! Your segment is {{segment}}.';
    $replaced = $campaignService->replaceVariables($content, $customer);

    expect($replaced)->toBe('Hello John Doe! Your segment is Champions.');
});

test('campaign calculates open rate', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create();

    $campaign = Campaign::create([
        'name' => 'Test',
        'channel' => 'email',
        'status' => 'sent',
        'subject' => 'Test',
        'content' => 'Test',
        'filter_type' => 'all',
        'created_by' => $user->id,
        'total_sent' => 10,
    ]);

    expect($campaign->open_rate)->toBe(0.0);
});
