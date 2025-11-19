<?php

namespace App\Services\Sms;

use App\Contracts\SmsGatewayInterface;

class MelipayamakGateway implements SmsGatewayInterface
{
    protected mixed $client = null;

    protected ?string $from = null;

    public function __construct()
    {
        $username = config('services.melipayamak.username');
        $password = config('services.melipayamak.password');
        $this->from = config('services.melipayamak.from');

        if (! $username || ! $password || ! $this->from) {
            return;
        }

        // Only create client if the package is installed
        if (! class_exists(\Melipayamak\MelipayamakApi::class)) {
            return;
        }

        $this->client = new \Melipayamak\MelipayamakApi($username, $password);
    }

    public function send(string $to, string $message): string
    {
        if (! $this->isConfigured()) {
            throw new \Exception('Melipayamak credentials not configured. Please set MELIPAYAMAK_USERNAME, MELIPAYAMAK_PASSWORD, and MELIPAYAMAK_FROM in your .env file.');
        }

        try {
            // Normalize phone number
            $normalizedPhone = $this->normalizePhoneNumber($to);

            // Send SMS using the official package
            $sms = $this->client->sms();
            $response = $sms->send($normalizedPhone, $this->from, $message);

            $json = json_decode($response);

            if (isset($json->Value) && $json->Value > 0) {
                return (string) $json->Value;
            }

            // Handle error response
            $errorCode = $json->Value ?? 'unknown';
            throw new \Exception("Melipayamak error code: {$errorCode}");
        } catch (\Exception $e) {
            throw new \Exception('Failed to send SMS via Melipayamak: ' . $e->getMessage());
        }
    }

    public function isConfigured(): bool
    {
        return $this->client !== null && $this->from !== null;
    }

    /**
     * Normalize phone number to Melipayamak format.
     */
    protected function normalizePhoneNumber(string $phone): string
    {
        // Remove any non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Remove Iran country code (98) if present
        if (str_starts_with($phone, '98') && strlen($phone) > 10) {
            $phone = substr($phone, 2);
        }

        // Add leading zero if not present (Iranian format)
        if (! str_starts_with($phone, '0') && strlen($phone) === 10) {
            $phone = '0' . $phone;
        }

        return $phone;
    }
}
