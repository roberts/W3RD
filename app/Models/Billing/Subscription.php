<?php

namespace App\Models\Billing;

use Laravel\Cashier\Subscription as CashierSubscription;

class Subscription extends CashierSubscription
{
    protected $fillable = [
        'billable_id',
        'billable_type',
        'type',
        'stripe_id',
        'stripe_status',
        'stripe_price',
        'quantity',
        'trial_ends_at',
        'ends_at',
        'provider', // Added for multi-platform support
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function isStripe(): bool
    {
        return $this->provider === 'stripe';
    }

    public function isApple(): bool
    {
        return $this->provider === 'apple';
    }

    public function isGoogle(): bool
    {
        return $this->provider === 'google';
    }

    public function isTelegram(): bool
    {
        return $this->provider === 'telegram';
    }

    public function isAdmin(): bool
    {
        return $this->provider === 'admin';
    }

    public function isLifetime(): bool
    {
        return $this->ends_at === null && $this->stripe_status === 'active';
    }
}
