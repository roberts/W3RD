<?php

namespace App\Enums;

enum MembershipPlan: string
{
    case FREE = 'free';
    case PRO = 'pro';
    case ELITE = 'elite';

    /**
     * Get the display name for the membership plan.
     */
    public function label(): string
    {
        return match ($this) {
            self::FREE => 'Free',
            self::PRO => 'Pro',
            self::ELITE => 'Elite',
        };
    }

    /**
     * Get the description for the membership plan.
     */
    public function description(): string
    {
        return match ($this) {
            self::FREE => 'Basic access with limited features',
            self::PRO => 'Enhanced features and priority support',
            self::ELITE => 'Premium access with all features unlocked',
        };
    }
}
