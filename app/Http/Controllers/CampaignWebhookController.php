<?php

namespace App\Http\Controllers;

use App\Models\CampaignEvent;
use App\Models\CampaignSend;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CampaignWebhookController extends Controller
{
    public function trackOpen(Request $request, string $send)
    {
        try {
            $campaignSend = CampaignSend::findOrFail($send);

            CampaignEvent::firstOrCreate(
                [
                    'campaign_send_id' => $campaignSend->id,
                    'event_type' => 'opened',
                ],
                [
                    'occurred_at' => now(),
                    'event_data' => [
                        'user_agent' => $request->userAgent(),
                        'ip' => $request->ip(),
                    ],
                ]
            );

            return response()->file(public_path('images/pixel.png'));
        } catch (\Exception $e) {
            Log::error('Failed to track email open', [
                'send_id' => $send,
                'error' => $e->getMessage(),
            ]);

            return response()->noContent();
        }
    }

    public function mailgunWebhook(Request $request)
    {
        try {
            $eventData = $request->all();
            $eventType = $eventData['event'] ?? null;
            $messageId = $eventData['message-id'] ?? $eventData['Message-Id'] ?? null;

            if (! $messageId || ! $eventType) {
                return response()->json(['status' => 'ignored'], 200);
            }

            $campaignSend = CampaignSend::where('external_id', $messageId)->first();

            if (! $campaignSend) {
                return response()->json(['status' => 'not_found'], 404);
            }

            $eventTypeMap = [
                'opened' => 'opened',
                'clicked' => 'clicked',
                'unsubscribed' => 'unsubscribed',
                'complained' => 'complained',
            ];

            if (isset($eventTypeMap[$eventType])) {
                CampaignEvent::create([
                    'campaign_send_id' => $campaignSend->id,
                    'event_type' => $eventTypeMap[$eventType],
                    'occurred_at' => now(),
                    'event_data' => $eventData,
                ]);
            }

            if ($eventType === 'bounced' || $eventType === 'failed') {
                $campaignSend->update([
                    'status' => 'bounced',
                    'error_message' => $eventData['reason'] ?? 'Bounced',
                ]);
            }

            return response()->json(['status' => 'success'], 200);
        } catch (\Exception $e) {
            Log::error('Mailgun webhook error', [
                'error' => $e->getMessage(),
                'data' => $request->all(),
            ]);

            return response()->json(['status' => 'error'], 500);
        }
    }

    public function twilioWebhook(Request $request)
    {
        try {
            $messageSid = $request->input('MessageSid');
            $messageStatus = $request->input('MessageStatus');

            if (! $messageSid) {
                return response()->xml('<Response></Response>', 200);
            }

            $campaignSend = CampaignSend::where('external_id', $messageSid)->first();

            if (! $campaignSend) {
                return response()->xml('<Response></Response>', 404);
            }

            if ($messageStatus === 'failed' || $messageStatus === 'undelivered') {
                $campaignSend->update([
                    'status' => 'failed',
                    'error_message' => $request->input('ErrorMessage', 'Failed to deliver'),
                ]);
            }

            return response()->xml('<Response></Response>', 200);
        } catch (\Exception $e) {
            Log::error('Twilio webhook error', [
                'error' => $e->getMessage(),
                'data' => $request->all(),
            ]);

            return response()->xml('<Response></Response>', 500);
        }
    }
}
