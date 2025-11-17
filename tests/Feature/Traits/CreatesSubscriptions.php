<?php

namespace Tests\Feature\Traits;

use App\Models\Auth\User;
use App\Models\Billing\Subscription;

trait CreatesSubscriptions
{
    /**
     * Create a Stripe subscription for the given user
     */
    protected function createStripeSubscription(User $user, string $status = 'active'): Subscription
    {
        return Subscription::factory()->stripe()->create([
            'user_id' => $user->id,
            'status' => $status,
        ]);
    }
    
    /**
     * Create an Apple subscription for the given user
     */
    protected function createAppleSubscription(User $user, string $status = 'active'): Subscription
    {
        return Subscription::factory()->apple()->create([
            'user_id' => $user->id,
            'status' => $status,
        ]);
    }
    
    /**
     * Create a Google subscription for the given user
     */
    protected function createGoogleSubscription(User $user, string $status = 'active'): Subscription
    {
        return Subscription::factory()->google()->create([
            'user_id' => $user->id,
            'status' => $status,
        ]);
    }
    
    /**
     * Create a Telegram subscription for the given user
     */
    protected function createTelegramSubscription(User $user, string $status = 'active'): Subscription
    {
        return Subscription::factory()->telegram()->create([
            'user_id' => $user->id,
            'status' => $status,
        ]);
    }
}
