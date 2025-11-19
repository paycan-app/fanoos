<?php

namespace App\Services;

use App\Contracts\SmsGatewayInterface;
use App\Services\Sms\MelipayamakGateway;
use App\Services\Sms\TwilioGateway;

class SmsService
{
    protected SmsGatewayInterface $gateway;

    public function __construct()
    {
        $this->gateway = $this->resolveGateway();
    }

    /**
     * Send an SMS message.
     *
     * @param  string  $to  The recipient's phone number
     * @param  string  $message  The message content
     * @return string The message ID from the gateway
     *
     * @throws \Exception
     */
    public function send(string $to, string $message): string
    {
        return $this->gateway->send($to, $message);
    }

    /**
     * Get the current SMS provider name.
     */
    public function getProviderName(): string
    {
        return config('services.sms.provider', 'twilio');
    }

    /**
     * Check if the gateway is properly configured.
     */
    public function isConfigured(): bool
    {
        return $this->gateway->isConfigured();
    }

    /**
     * Resolve the appropriate gateway based on configuration.
     */
    protected function resolveGateway(): SmsGatewayInterface
    {
        $provider = config('services.sms.provider', 'twilio');

        return match ($provider) {
            'melipayamak' => new MelipayamakGateway,
            'twilio' => new TwilioGateway,
            default => new TwilioGateway,
        };
    }
}
