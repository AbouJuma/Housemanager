<?php

namespace App\Modules\Tenants\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BeemsSmsService
{
    protected string $apiKey;
    protected string $secretKey;
    protected string $senderName;
    protected string $apiUrl = 'https://apisms.beem.africa/v1/send';

    public function __construct()
    {
        $this->apiKey = config('services.beems.api_key', '48a8c40076933db5');
        $this->secretKey = config('services.beems.secret_key', 'ZjE4NjQxMzBhODIwMmQzNjZjMWE5YjJmODY3YzEyZmM0NzliODI1NDE3Y2U0NjAzYmUyOWE3NWU4ODcxYzVkYg==');
        $this->senderName = config('services.beems.sender_name', 'DocTouch');
    }

    /**
     * Send SMS message
     */
    public function send(string $phone, string $message): bool
    {
        try {
            // Format phone number - ensure it starts with country code
            $phone = $this->formatPhoneNumber($phone);

            $postData = [
                'source_addr' => $this->senderName,
                'encoding' => 0,
                'schedule_time' => '',
                'message' => $message,
                'recipients' => [
                    [
                        'recipient_id' => '1',
                        'dest_addr' => '+' . $phone,
                    ],
                ],
            ];

            $authHeader = 'Basic ' . base64_encode($this->apiKey . ':' . $this->secretKey);

            $response = Http::withHeaders([
                'Authorization' => $authHeader,
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl, $postData);

            if ($response->successful()) {
                Log::info('SMS sent successfully via Beems', [
                    'phone' => $phone,
                    'message' => substr($message, 0, 50) . '...',
                    'response' => $response->body(),
                ]);
                return true;
            }

            Log::error('Failed to send SMS via Beems', [
                'phone' => $phone,
                'response' => $response->body(),
                'status' => $response->status(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Exception sending SMS via Beems', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Format phone number to ensure it has country code
     * Handles formats: 0682676819, +255682676819, 255682676819
     */
    protected function formatPhoneNumber(string $phone): string
    {
        // Remove any non-numeric characters (including +)
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // If starts with 0, replace with Tanzania country code
        if (str_starts_with($phone, '0')) {
            $phone = '255' . substr($phone, 1);
        }

        // If doesn't start with country code (255), add it
        if (!str_starts_with($phone, '255')) {
            $phone = '255' . $phone;
        }

        return $phone;
    }

    /**
     * Send welcome message to tenant
     */
    public function sendWelcomeMessage(array $data): bool
    {
        $message = $this->buildWelcomeMessage($data);

        return $this->send($data['phone'], $message);
    }

    /**
     * Build welcome message in Swahili
     */
    protected function buildWelcomeMessage(array $data): string
    {
        return sprintf(
            "karibu %s kwenye Apartment %s login kwa www.apartments.ecofieldgroup.com phone:%s utapata otp\nLipia kodi yako kupitia control namba yako ni %s bank %s a/c no.%s kwaajili ya malipo kiasi cha kulipia ni Tsh %s\nplease pay your rent via Control number %s Bank %s",
            $data['tenant_name'],
            $data['company_name'],
            $data['phone'],
            $data['control_number'],
            $data['bank_name'],
            $data['account_number'],
            number_format($data['rent_amount'], 0),
            $data['control_number'],
            $data['bank_name']
        );
    }
}
