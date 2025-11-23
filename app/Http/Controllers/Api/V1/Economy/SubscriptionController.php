<?php

namespace App\Http\Controllers\Api\V1\Economy;

use App\Http\Controllers\Controller;
use App\Http\Requests\Economy\SubscribeRequest;
use App\Http\Traits\ApiResponses;
use App\Models\Billing\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    use ApiResponses;

    /**
     * Start a new subscription via Stripe.
     *
     * POST /v1/economy/subscribe
     */
    public function subscribe(SubscribeRequest $request): JsonResponse
    {
        $user = $request->user();
        $plan = $request->validated()['plan'];

        try {
            // Create Stripe checkout session
            $checkout = $user->newSubscription($plan, config("protocol.stripe_prices.{$plan}"))
                ->checkout([
                    'success_url' => config('app.url').'/billing/success',
                    'cancel_url' => config('app.url').'/billing/cancel',
                ]);

            return $this->dataResponse([
                /** @phpstan-ignore-next-line */
                'checkout_url' => $checkout->url,
                /** @phpstan-ignore-next-line */
                'session_id' => $checkout->id,
            ], 'Checkout session created successfully.');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create subscription: '.$e->getMessage(), 500);
        }
    }

    /**
     * Get the authenticated user's subscription status.
     *
     * GET /v1/economy/subscription
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        /** @var Subscription|null $subscription */
        $subscription = $user->subscriptions()->where('stripe_status', 'active')->first();

        if (! $subscription) {
            return $this->dataResponse([
                'subscribed' => false,
                'plan' => 'free',
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
     * Cancel the user's active subscription.
     *
     * POST /v1/economy/subscription/cancel
     */
    public function cancel(Request $request): JsonResponse
    {
        $user = $request->user();

        /** @var Subscription|null $subscription */
        $subscription = $user->subscriptions()->where('stripe_status', 'active')->first();

        if (! $subscription) {
            return $this->errorResponse('No active subscription found.', 404);
        }

        try {
            $subscription->cancel();

            return $this->messageResponse('Subscription cancelled successfully. Access will continue until the end of the billing period.');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to cancel subscription: '.$e->getMessage(), 500);
        }
    }
}
