<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ReceiptVerificationService
{
    /**
     * Verify receipt from various platforms.
     */
    public function verify(string $provider, string $receipt, int $userId): array
    {
        return match ($provider) {
            'apple' => $this->verifyAppleReceipt($receipt, $userId),
            'google' => $this->verifyGoogleReceipt($receipt, $userId),
            'telegram' => $this->verifyTelegramReceipt($receipt, $userId),
            default => throw new \InvalidArgumentException("Unsupported provider: {$provider}"),
        };
    }

    /**
     * Verify Apple App Store receipt.
     */
    protected function verifyAppleReceipt(string $receipt, int $userId): array
    {
        $password = config('services.apple.shared_secret');

        if (! $password) {
            Log::error('Apple shared secret not configured');
            throw new \Exception('Apple receipt verification not configured');
        }

        // Try production endpoint first
        $response = Http::post('https://buy.itunes.apple.com/verifyReceipt', [
            'receipt-data' => $receipt,
            'password' => $password,
        ]);

        $data = $response->json();

        // If status is 21007, receipt is from sandbox, retry with sandbox endpoint
        if (isset($data['status']) && $data['status'] === 21007) {
            $response = Http::post('https://sandbox.itunes.apple.com/verifyReceipt', [
                'receipt-data' => $receipt,
                'password' => $password,
            ]);
            $data = $response->json();
        }

        // Status 0 means success
        $verified = isset($data['status']) && $data['status'] === 0;

        return [
            'verified' => $verified,
            'subscription_status' => $verified ? 'active' : 'invalid',
            'expires_at' => $data['latest_receipt_info'][0]['expires_date'] ?? null,
        ];
    }

    /**
     * Verify Google Play receipt.
     */
    protected function verifyGoogleReceipt(string $receipt, int $userId): array
    {
        // In production, use Google Play Developer API
        // This is a simplified example
        Log::info('Google receipt verification', ['receipt' => $receipt, 'user_id' => $userId]);

        // For now, return mock verification
        return [
            'verified' => true,
            'subscription_status' => 'active',
            'expires_at' => now()->addMonth()->toIso8601String(),
        ];
    }

    /**
     * Verify Telegram Stars payment.
     */
    protected function verifyTelegramReceipt(string $receipt, int $userId): array
    {
        // In production, verify with Telegram Bot API
        Log::info('Telegram receipt verification', ['receipt' => $receipt, 'user_id' => $userId]);

        // For now, return mock verification
        return [
            'verified' => true,
            'subscription_status' => 'active',
            'expires_at' => now()->addMonth()->toIso8601String(),
        ];
    }
}
