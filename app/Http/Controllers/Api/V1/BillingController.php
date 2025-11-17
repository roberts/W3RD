<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\CreateStripeSubscriptionRequest;
use App\Http\Requests\Billing\VerifyAppleReceiptRequest;
use App\Http\Requests\Billing\VerifyGoogleReceiptRequest;
use App\Http\Requests\Billing\VerifyTelegramReceiptRequest;
use App\Models\Billing\Subscription;
use App\Services\AppleReceiptValidator;
use App\Services\GooglePurchaseValidator;
use App\Services\TelegramPaymentValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function __construct(
        protected AppleReceiptValidator $appleValidator,
        protected GooglePurchaseValidator $googleValidator,
        protected TelegramPaymentValidator $telegramValidator
    ) {}

    /**
     * Get available subscription plans.
     */
    public function getPlans(): JsonResponse
    {
        $plans = config('protocol.subscription_plans');

        return response()->json([
            'data' => $plans,
        ]);
    }

    /**
     * Get the authenticated user's subscription status.
     */
    public function getStatus(Request $request): JsonResponse
    {
        $user = $request->user();

        /** @var Subscription|null $subscription */
        $subscription = $user->subscriptions()->where('stripe_status', 'active')->first();

        if (! $subscription) {
            return response()->json([
                'data' => [
                    'subscribed' => false,
                    'plan' => 'basic',
                ],
            ]);
        }

        return response()->json([
            'data' => [
                'subscribed' => true,
                'plan' => $subscription->type,
                'provider' => $subscription->provider,
                'status' => $subscription->stripe_status,
                'trial_ends_at' => $subscription->trial_ends_at,
                'ends_at' => $subscription->ends_at,
                'is_lifetime' => $subscription->ends_at === null,
            ],
        ]);
    }

    /**
     * Create a Stripe Checkout session for subscription.
     */
    public function createStripeSubscription(CreateStripeSubscriptionRequest $request): JsonResponse
    {
        $user = $request->user();
        $plan = $request->input('plan');

        // Get plan details from config
        $plans = collect(config('protocol.subscription_plans'));
        $planConfig = $plans->firstWhere('id', $plan);

        if (! $planConfig || ! isset($planConfig['stripe_price_id'])) {
            return response()->json([
                'message' => 'Invalid plan selected.',
            ], 400);
        }

        try {
            $checkout = $user->newSubscription($plan, $planConfig['stripe_price_id'])
                ->checkout([
                    'success_url' => config('app.url').'/billing/success?session_id={CHECKOUT_SESSION_ID}',
                    'cancel_url' => config('app.url').'/billing/cancel',
                ]);

            return response()->json([
                'data' => [
                    /** @phpstan-ignore-next-line */
                    'checkout_url' => $checkout->url,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create checkout session: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Stripe Customer Portal URL for managing subscription.
     */
    public function manageSubscription(Request $request): JsonResponse
    {
        $user = $request->user();

        try {
            $portalSession = $user->billingPortalUrl(
                config('app.url').'/billing'
            );

            return response()->json([
                'data' => [
                    'portal_url' => $portalSession,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create portal session: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verify Apple IAP receipt.
     */
    public function verifyAppleReceipt(VerifyAppleReceiptRequest $request): JsonResponse
    {
        $user = $request->user();

        try {
            $result = $this->appleValidator->validate($request->input('transaction_id'));

            // Create or update subscription
            /** @var Subscription $subscription */
            $subscription = $user->subscriptions()->updateOrCreate(
                ['stripe_id' => $request->input('transaction_id')],
                [
                    'type' => 'premium',
                    'stripe_status' => 'active',
                    'provider' => 'apple',
                    'ends_at' => null, // Set based on Apple response if needed
                ]
            );

            return response()->json([
                'data' => [
                    'verified' => true,
                    'subscription_id' => $subscription->id,
                ],
                'message' => 'Apple receipt verified successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to verify Apple receipt: '.$e->getMessage(),
            ], 400);
        }
    }

    /**
     * Verify Google Play purchase.
     */
    public function verifyGoogleReceipt(VerifyGoogleReceiptRequest $request): JsonResponse
    {
        $user = $request->user();

        try {
            $result = $this->googleValidator->validate(
                $request->input('product_id'),
                $request->input('token')
            );

            if (! $result['valid']) {
                return response()->json([
                    'message' => 'Invalid Google Play purchase.',
                ], 400);
            }

            // Create or update subscription
            /** @var Subscription $subscription */
            $subscription = $user->subscriptions()->updateOrCreate(
                ['stripe_id' => $result['order_id']],
                [
                    'type' => 'premium',
                    'stripe_status' => 'active',
                    'provider' => 'google',
                    'ends_at' => null, // Set based on Google response if needed
                ]
            );

            return response()->json([
                'data' => [
                    'verified' => true,
                    'subscription_id' => $subscription->id,
                ],
                'message' => 'Google Play purchase verified successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to verify Google Play purchase: '.$e->getMessage(),
            ], 400);
        }
    }

    /**
     * Verify Telegram payment.
     */
    public function verifyTelegramReceipt(VerifyTelegramReceiptRequest $request): JsonResponse
    {
        $user = $request->user();

        try {
            $isValid = $this->telegramValidator->validate(
                $request->input('data'),
                $request->input('hash')
            );

            if (! $isValid) {
                return response()->json([
                    'message' => 'Invalid Telegram payment hash.',
                ], 400);
            }

            $paymentDetails = $this->telegramValidator->extractPaymentDetails($request->input('data'));

            // Create or update subscription
            /** @var Subscription $subscription */
            $subscription = $user->subscriptions()->updateOrCreate(
                ['stripe_id' => $paymentDetails['telegram_payment_charge_id']],
                [
                    'type' => 'premium',
                    'stripe_status' => 'active',
                    'provider' => 'telegram',
                    'ends_at' => null,
                ]
            );

            return response()->json([
                'data' => [
                    'verified' => true,
                    'subscription_id' => $subscription->id,
                ],
                'message' => 'Telegram payment verified successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to verify Telegram payment: '.$e->getMessage(),
            ], 400);
        }
    }
}
