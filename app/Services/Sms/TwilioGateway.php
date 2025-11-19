<?php

namespace App\Services\Sms;

use App\Contracts\SmsGatewayInterface;

class TwilioGateway implements SmsGatewayInterface
{
    protected mixed $client = null;

    protected ?string $from = null;

    public function __construct()
    {
        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $this->from = config('services.twilio.from');

        if (! $sid || ! $token || ! $this->from) {
            return;
        }

        // Only create Twilio client if the package is installed
        if (! class_exists(\Twilio\Rest\Client::class)) {
            return;
        }

        $this->client = new \Twilio\Rest\Client($sid, $token);
    }

    public function send(string $to, string $message): string
    {
        if (! $this->isConfigured()) {
            throw new \Exception('Twilio credentials not configured. Please set TWILIO_SID, TWILIO_AUTH_TOKEN, and TWILIO_FROM in your .env file.');
        }

        try {
            $result = $this->client->messages->create(
                $to,
                [
                    'from' => $this->from,
                    'body' => $message,
                ]
            );

            return $result->sid;
        } catch (\Exception $e) {
            throw new \Exception('Failed to send SMS via Twilio: '.$e->getMessage());
        }
    }

    public function isConfigured(): bool
    {
        return $this->client !== null && $this->from !== null;
    }
}
