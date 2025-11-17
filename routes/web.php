<?php

use App\Http\Controllers\CampaignWebhookController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/campaign/track/open/{send}', [CampaignWebhookController::class, 'trackOpen'])->name('campaign.track.open');
Route::post('/webhooks/mailgun', [CampaignWebhookController::class, 'mailgunWebhook'])->name('webhooks.mailgun');
Route::post('/webhooks/twilio', [CampaignWebhookController::class, 'twilioWebhook'])->name('webhooks.twilio');

require __DIR__.'/settings.php';
