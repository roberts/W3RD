<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Traits\ApiResponses;
use App\Models\Alert;
use App\Models\Billing\Subscription;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;
use Symfony\Component\HttpFoundation\Response;

class StripeWebhookController extends CashierWebhookController
{
    use ApiResponses;

    /**
     * Handle subscription created.
     */
    protected function handleCustomerSubscriptionCreated(array $payload): Response
    {
        return parent::handleCustomerSubscriptionCreated($payload);

        // Add custom logic if needed
        // For example, send welcome alert
    }

    /**
     * Handle subscription updated.
     */
    protected function handleCustomerSubscriptionUpdated(array $payload): ?Response
    {
        return parent::handleCustomerSubscriptionUpdated($payload);

        // Add custom logic if needed
    }

    /**
     * Handle subscription deleted.
     */
    protected function handleCustomerSubscriptionDeleted(array $payload): Response
    {
        return parent::handleCustomerSubscriptionDeleted($payload);

        // Add custom logic if needed
        // For example, send cancellation alert
    }

    /**
     * Handle payment failed.
     */
    protected function handleInvoicePaymentFailed(array $payload): Response
    {
        // Send payment failed alert
        $subscription = $this->findSubscription($payload['data']['object']['subscription'] ?? null);

        if ($subscription && $subscription->user) {
            Alert::create([
                /** @phpstan-ignore-next-line */
                'user_id' => $subscription->user->id,
                'type' => 'billing_issue',
                'data' => [
                    'message' => 'Your subscription payment failed. Please update your payment method.',
                    'action_url' => '/billing/manage',
                ],
            ]);
        }

        return $this->successResponse(null, 'Webhook Handled');
    }

    /**
     * Find subscription by Stripe ID.
     */
    private function findSubscription(?string $stripeId): ?Subscription
    {
        if (! $stripeId) {
            return null;
        }

        return Subscription::where('stripe_id', $stripeId)->first();
    }
}
