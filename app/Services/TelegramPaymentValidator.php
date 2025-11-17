<?php

namespace App\Services;

class TelegramPaymentValidator
{
    /**
     * Validate Telegram Mini App payment data.
     */
    public function validate(array $data, string $hash): bool
    {
        $botToken = config('services.telegram.bot_token');

        // Build data-check-string
        $checkArray = [];
        foreach ($data as $key => $value) {
            if ($key === 'hash') {
                continue;
            }
            $checkArray[] = "{$key}={$value}";
        }
        sort($checkArray);
        $dataCheckString = implode("\n", $checkArray);

        // Generate secret key
        $secretKey = hash_hmac('sha256', $botToken, 'WebAppData', true);

        // Calculate signature
        $calculatedHash = hash_hmac('sha256', $dataCheckString, $secretKey);

        // Compare hashes
        return hash_equals($calculatedHash, $hash);
    }

    /**
     * Extract payment details from validated data.
     */
    public function extractPaymentDetails(array $data): array
    {
        return [
            'telegram_payment_charge_id' => $data['telegram_payment_charge_id'] ?? null,
            'total_amount' => $data['total_amount'] ?? null,
            'currency' => $data['currency'] ?? null,
            'invoice_payload' => $data['invoice_payload'] ?? null,
        ];
    }
}
