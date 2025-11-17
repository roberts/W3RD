<?php

namespace Tests\Feature\Helpers;

use App\Models\Auth\User;
use App\Models\Billing\Plan;
use App\Models\Billing\Subscription;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;

class BillingHelper
{
    /**
     * Create a subscription for a user.
     */
    public static function createSubscription(User $user, ?Plan $plan = null, array $attributes = []): Subscription
    {
        if (! $plan) {
            $plan = Plan::where('slug', 'starter')->first() ?? Plan::factory()->create(['slug' => 'starter']);
        }

        return Subscription::factory()->create(array_merge([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ], $attributes));
    }

    /**
     * Create an expired subscription.
     */
    public static function createExpiredSubscription(User $user): Subscription
    {
        return self::createSubscription($user, null, [
            'status' => 'expired',
            'current_period_end' => now()->subDay(),
        ]);
    }

    /**
     * Create a suspended subscription.
     */
    public static function createSuspendedSubscription(User $user): Subscription
    {
        return self::createSubscription($user, null, [
            'status' => 'suspended',
        ]);
    }

    /**
     * Verify a receipt from a mobile platform.
     */
    public static function verifyReceipt(User $user, string $provider, array $receiptData): TestResponse
    {
        return test()->actingAs($user)->postJson("/api/v1/billing/{$provider}/verify", $receiptData);
    }

    /**
     * Get subscription status for a user.
     */
    public static function getSubscriptionStatus(User $user): TestResponse
    {
        return test()->actingAs($user)->getJson('/api/v1/billing/status');
    }

    /**
     * Get available subscription plans.
     */
    public static function getPlans(): TestResponse
    {
        return test()->getJson('/api/v1/billing/plans');
    }

    /**
     * Create a Stripe checkout session.
     */
    public static function createStripeCheckout(User $user, string $planSlug): TestResponse
    {
        return test()->actingAs($user)->postJson('/api/v1/billing/subscribe', [
            'plan' => $planSlug,
        ]);
    }

    /**
     * Get Stripe customer portal URL.
     */
    public static function getStripePortal(User $user): TestResponse
    {
        return test()->actingAs($user)->getJson('/api/v1/billing/manage');
    }

    /**
     * Mock Stripe webhook signature verification.
     */
    public static function mockStripeWebhook(array $eventData): void
    {
        Http::fake([
            'https://api.stripe.com/*' => Http::response($eventData, 200),
        ]);
    }

    /**
     * Fake Apple receipt verification.
     */
    public static function fakeAppleReceipt(bool $valid = true): array
    {
        return [
            'receipt_data' => base64_encode(json_encode([
                'transaction_id' => 'apple_' . rand(100000, 999999),
                'product_id' => 'com.gamerprotocol.starter',
                'purchase_date_ms' => now()->timestamp * 1000,
            ])),
            'valid' => $valid,
        ];
    }

    /**
     * Fake Google receipt verification.
     */
    public static function fakeGoogleReceipt(bool $valid = true): array
    {
        return [
            'purchase_token' => 'google_' . bin2hex(random_bytes(16)),
            'product_id' => 'com.gamerprotocol.starter',
            'order_id' => 'GPA.' . rand(1000, 9999),
            'purchase_time' => now()->timestamp * 1000,
            'valid' => $valid,
        ];
    }

    /**
     * Fake Telegram receipt verification.
     */
    public static function fakeTelegramReceipt(bool $valid = true): array
    {
        return [
            'telegram_payment_charge_id' => 'tg_' . bin2hex(random_bytes(16)),
            'provider_payment_charge_id' => 'provider_' . bin2hex(random_bytes(16)),
            'total_amount' => 999,
            'currency' => 'USD',
            'valid' => $valid,
        ];
    }
}
