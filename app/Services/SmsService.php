<?php

namespace App\Services;

use Twilio\Rest\Client;

class SmsService
{
    protected ?Client $client = null;

    protected ?string $from = null;

    public function __construct()
    {
        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $this->from = config('services.twilio.from');

        if (! $sid || ! $token || ! $this->from) {
            return;
        }

        $this->client = new Client($sid, $token);
    }

    public function send(string $to, string $message): string
    {
        if (! $this->client || ! $this->from) {
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
            throw new \Exception('Failed to send SMS: '.$e->getMessage());
        }
    }
}
