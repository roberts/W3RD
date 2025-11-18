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

        return $this->dataResponse($plans);
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
            return $this->dataResponse([
                'subscribed' => false,
                'plan' => 'basic',
            ]);
        }

        return $this->dataResponse([
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

        $checkout = $this->handleServiceCall(
            fn () => $user->newSubscription($plan, $planConfig['stripe_price_id'])
                ->checkout([
                    'success_url' => config('app.url').'/billing/success?session_id={CHECKOUT_SESSION_ID}',
                    'cancel_url' => config('app.url').'/billing/cancel',
                ]),
            'Failed to create checkout session'
        );

        if ($checkout instanceof JsonResponse) {
            return $checkout;
        }

        return $this->dataResponse([
            'checkout_url' => $checkout->url,
        ]);
    }

    /**
     * Get Stripe Customer Portal URL for managing subscription.
     */
    public function manageSubscription(Request $request): JsonResponse
    {
        $user = $request->user();

        $portalSession = $this->handleServiceCall(
            fn () => $user->billingPortalUrl(config('app.url').'/billing'),
            'Failed to create portal session'
        );

        if ($portalSession instanceof JsonResponse) {
            return $portalSession;
        }

        return $this->dataResponse([
            'portal_url' => $portalSession,
        ]);
    }

    /**
     * Verify Apple IAP receipt.
     */
    public function verifyAppleReceipt(VerifyAppleReceiptRequest $request): JsonResponse
    {
        $user = $request->user();

        $result = $this->handleServiceCall(
            fn () => $this->appleValidator->validate($request->input('transaction_id')),
            'Failed to verify Apple receipt'
        );

        if ($result instanceof JsonResponse) {
            return $result;
        }

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

        return $this->dataResponse([
            'verified' => true,
            'subscription_id' => $subscription->id,
        ], 'Apple receipt verified successfully.');
    }

    /**
     * Verify Google Play purchase.
     */
    public function verifyGoogleReceipt(VerifyGoogleReceiptRequest $request): JsonResponse
    {
        $user = $request->user();

        $result = $this->handleServiceCall(
            fn () => $this->googleValidator->validate(
                $request->input('product_id'),
                $request->input('token')
            ),
            'Failed to verify Google Play purchase'
        );

        if ($result instanceof JsonResponse) {
            return $result;
        }

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

        return $this->dataResponse([
            'verified' => true,
            'subscription_id' => $subscription->id,
        ], 'Google Play purchase verified successfully.');
    }

    /**
     * Verify Telegram payment.
     */
    public function verifyTelegramReceipt(VerifyTelegramReceiptRequest $request): JsonResponse
    {
        $user = $request->user();

        $isValid = $this->handleServiceCall(
            fn () => $this->telegramValidator->validate(
                $request->input('data'),
                $request->input('hash')
            ),
            'Failed to verify Telegram payment'
        );

        if ($isValid instanceof JsonResponse) {
            return $isValid;
        }

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

        return $this->dataResponse([
            'verified' => true,
            'subscription_id' => $subscription->id,
        ], 'Telegram payment verified successfully.');
    }
}
