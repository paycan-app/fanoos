<?php

use App\Filament\Resources\Campaigns\Pages\CreateCampaign;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('can render create campaign page', function () {
    Livewire::test(CreateCampaign::class)
        ->assertSuccessful();
});
