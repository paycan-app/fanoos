<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Services\CampaignService;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendCampaignJob implements ShouldQueue
{
    use Batchable, Queueable;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(
        public Campaign $campaign
    ) {}

    public function handle(CampaignService $campaignService): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $recipients = $campaignService->getRecipients($this->campaign);

        $chunks = $recipients->chunk(100);

        foreach ($chunks as $chunk) {
            foreach ($chunk as $customer) {
                SendCampaignMessageJob::dispatch($this->campaign, $customer);
            }
        }

        $this->campaign->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }
}
