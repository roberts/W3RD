<?php

namespace App\Services\Economy;

use App\Exceptions\PaymentValidationException;
use Google\Client as GoogleClient;
use Google\Service\AndroidPublisher;

class GooglePurchaseValidator
{
    protected GoogleClient $client;

    public function __construct()
    {
        $this->client = new GoogleClient;

        $serviceAccountJson = config('services.google.service_account_json');
        if ($serviceAccountJson) {
            $this->client->setAuthConfig(json_decode($serviceAccountJson, true));
            $this->client->addScope(AndroidPublisher::ANDROIDPUBLISHER);
        }
    }

    /**
     * Validate a Google Play purchase.
     */
    public function validate(string $productId, string $token): array
    {
        $packageName = config('services.google.package_name');

        $service = new AndroidPublisher($this->client);

        try {
            $purchase = $service->purchases_products->get(
                $packageName,
                $productId,
                $token
            );

            return [
                'valid' => $purchase->getPurchaseState() === 0, // 0 = purchased
                'order_id' => $purchase->getOrderId(),
                'purchase_time' => $purchase->getPurchaseTimeMillis(),
                'product_id' => $productId,
                'acknowledgement_state' => $purchase->getAcknowledgementState(),
            ];
        } catch (\Exception $e) {
            throw new PaymentValidationException(
                'Failed to validate Google Play purchase: '.$e->getMessage(),
                'google',
                ['product_id' => $productId, 'error' => $e->getMessage()]
            );
        }
    }

    /**
     * Validate a Google Play subscription.
     */
    public function validateSubscription(string $subscriptionId, string $token): array
    {
        $packageName = config('services.google.package_name');

        $service = new AndroidPublisher($this->client);

        try {
            $subscription = $service->purchases_subscriptions->get(
                $packageName,
                $subscriptionId,
                $token
            );

            return [
                'valid' => in_array($subscription->getPaymentState(), [0, 1]), // 0 = pending, 1 = received
                'order_id' => $subscription->getOrderId(),
                'start_time' => $subscription->getStartTimeMillis(),
                'expiry_time' => $subscription->getExpiryTimeMillis(),
                'auto_renewing' => $subscription->getAutoRenewing(),
            ];
        } catch (\Exception $e) {
            throw new PaymentValidationException(
                'Failed to validate Google Play subscription: '.$e->getMessage(),
                'google',
                ['subscription_id' => $subscriptionId, 'error' => $e->getMessage()]
            );
        }
    }
}
