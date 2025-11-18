<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\CreateStripeSubscriptionRequest;
use App\Http\Requests\Billing\VerifyAppleReceiptRequest;
use App\Http\Requests\Billing\VerifyGoogleReceiptRequest;
use App\Http\Requests\Billing\VerifyTelegramReceiptRequest;
use App\Http\Traits\ApiResponses;
use App\Models\Billing\Subscription;
use App\Services\AppleReceiptValidator;
use App\Services\GooglePurchaseValidator;
use App\Services\TelegramPaymentValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    use ApiResponses;
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

        return $this->successResponse($plans);
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
            return $this->successResponse([
                'subscribed' => false,
                'plan' => 'basic',
            ]);
        }

        return $this->successResponse([
            'subscribed' => true,
            'plan' => $subscription->type,
            'provider' => $subscription->provider,
            'status' => $subscription->stripe_status,
            'trial_ends_at' => $subscription->trial_ends_at,
            'ends_at' => $subscription->ends_at,
            'is_lifetime' => $subscription->ends_at === null,
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
            return $this->errorResponse('Invalid plan selected.');
        }

        try {
            $checkout = $user->newSubscription($plan, $planConfig['stripe_price_id'])
                ->checkout([
                    'success_url' => config('app.url').'/billing/success?session_id={CHECKOUT_SESSION_ID}',
                    'cancel_url' => config('app.url').'/billing/cancel',
                ]);

            return $this->successResponse([
                /** @phpstan-ignore-next-line */
                'checkout_url' => $checkout->url,
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create checkout session: '.$e->getMessage(), 500);
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

            return $this->successResponse([
                'portal_url' => $portalSession,
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create portal session: '.$e->getMessage(), 500);
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

            return $this->successResponse([
                'verified' => true,
                'subscription_id' => $subscription->id,
            ], 'Apple receipt verified successfully.');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to verify Apple receipt: '.$e->getMessage());
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
                return $this->errorResponse('Invalid Google Play purchase.');
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

            return $this->successResponse([
                'verified' => true,
                'subscription_id' => $subscription->id,
            ], 'Google Play purchase verified successfully.');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to verify Google Play purchase: '.$e->getMessage());
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
                return $this->errorResponse('Invalid Telegram payment hash.');
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

            return $this->successResponse([
                'verified' => true,
                'subscription_id' => $subscription->id,
            ], 'Telegram payment verified successfully.');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to verify Telegram payment: '.$e->getMessage());
        }
    }
}
