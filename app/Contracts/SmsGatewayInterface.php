<?php

namespace App\Contracts;

interface SmsGatewayInterface
{
    /**
     * Send an SMS message.
     *
     * @param  string  $to  The recipient's phone number
     * @param  string  $message  The message content
     * @return string The message ID from the gateway
     *
     * @throws \Exception
     */
    public function send(string $to, string $message): string;

    /**
     * Check if the gateway is properly configured.
     */
    public function isConfigured(): bool;
}
