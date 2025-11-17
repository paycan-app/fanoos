<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\Customer;
use App\Services\CampaignService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class SendCampaignMessageJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public Campaign $campaign,
        public Customer $customer
    ) {}

    public function handle(CampaignService $campaignService): void
    {
        $key = 'campaign:'.$this->campaign->channel;

        RateLimiter::attempt(
            $key,
            $perMinute = $this->campaign->channel === 'email' ? 100 : 30,
            function () use ($campaignService) {
                try {
                    $campaignService->sendToCustomer($this->campaign, $this->customer);
                } catch (\Exception $e) {
                    Log::error('Failed to send campaign message', [
                        'campaign_id' => $this->campaign->id,
                        'customer_id' => $this->customer->id,
                        'error' => $e->getMessage(),
                    ]);

                    throw $e;
                }
            },
            $decay = 60
        );
    }
}
