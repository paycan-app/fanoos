<?php

namespace App\Services;

use App\Jobs\SendCampaignJob;
use App\Models\Campaign;
use App\Models\CampaignSend;
use App\Models\Customer;
use App\Notifications\CampaignEmailNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class CampaignService
{
    public function __construct(
        protected SmsService $smsService
    ) {}

    public function getRecipients(Campaign $campaign): Collection
    {
        return $campaign->getFilteredCustomers();
    }

    public function sendTestMessage(Campaign $campaign, string $recipient): bool
    {
        try {
            $testCustomer = new Customer([
                'id' => 'test-'.uniqid(),
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => $campaign->channel === 'email' ? $recipient : 'test@example.com',
                'phone' => $campaign->channel === 'sms' ? $recipient : null,
            ]);

            $content = $this->replaceVariables($campaign->content, $testCustomer);

            if ($campaign->channel === 'email') {
                Notification::route('mail', $recipient)
                    ->notify(new CampaignEmailNotification(
                        subject: '[TEST] '.$campaign->subject,
                        content: $content,
                        campaignSendId: null
                    ));
            } else {
                $this->smsService->send($recipient, '[TEST] '.$content);
            }

            return true;
        } catch (\Exception $e) {
            report($e);

            return false;
        }
    }

    public function processCampaign(Campaign $campaign): void
    {
        $recipients = $this->getRecipients($campaign);

        $campaign->update([
            'status' => 'sending',
            'total_recipients' => $recipients->count(),
        ]);

        SendCampaignJob::dispatch($campaign);
    }

    public function sendToCustomer(Campaign $campaign, Customer $customer): CampaignSend
    {
        $campaignSend = CampaignSend::create([
            'campaign_id' => $campaign->id,
            'customer_id' => $customer->id,
            'status' => 'pending',
        ]);

        try {
            $content = $this->replaceVariables($campaign->content, $customer);

            if ($campaign->channel === 'email') {
                $this->sendEmail($campaignSend, $customer, $campaign->subject, $content);
            } else {
                $this->sendSms($campaignSend, $customer, $content);
            }

            $campaign->increment('total_sent');

            return $campaignSend;
        } catch (\Exception $e) {
            $campaignSend->markAsFailed($e->getMessage());
            $campaign->increment('total_failed');

            throw $e;
        }
    }

    protected function sendEmail(CampaignSend $campaignSend, Customer $customer, string $subject, string $content): void
    {
        if (empty($customer->email)) {
            throw new \Exception('Customer does not have an email address');
        }

        Notification::route('mail', $customer->email)
            ->notify(new CampaignEmailNotification(
                subject: $subject,
                content: $content,
                campaignSendId: $campaignSend->id
            ));

        $campaignSend->markAsSent();
    }

    protected function sendSms(CampaignSend $campaignSend, Customer $customer, string $content): void
    {
        if (empty($customer->phone)) {
            throw new \Exception('Customer does not have a phone number');
        }

        $externalId = $this->smsService->send($customer->phone, $content);

        $campaignSend->markAsSent($externalId);
    }

    public function replaceVariables(string $content, Customer $customer): string
    {
        $replacements = [
            '{{first_name}}' => $customer->first_name ?? '',
            '{{last_name}}' => $customer->last_name ?? '',
            '{{email}}' => $customer->email ?? '',
            '{{segment}}' => $customer->segment ?? '',
            '{{monetary}}' => number_format($customer->monetary ?? 0, 2),
            '{{frequency}}' => $customer->frequency ?? 0,
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }

    public function getAvailableSegments(): array
    {
        return Customer::query()
            ->whereNotNull('segment')
            ->distinct()
            ->pluck('segment')
            ->toArray();
    }

    public function getAvailableCountries(): array
    {
        return Customer::query()
            ->whereNotNull('country')
            ->distinct()
            ->pluck('country')
            ->toArray();
    }

    public function getAvailableLabels(): array
    {
        $labels = Customer::query()
            ->whereNotNull('labels')
            ->pluck('labels')
            ->flatten()
            ->unique()
            ->filter()
            ->values()
            ->toArray();

        return $labels;
    }
}
