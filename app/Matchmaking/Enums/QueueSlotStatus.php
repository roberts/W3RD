<?php

namespace App\Matchmaking\Enums;

enum QueueSlotStatus: string
{
    case ACTIVE = 'active';
    case MATCHED = 'matched';
    case CANCELLED = 'cancelled';
    case EXPIRED = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::MATCHED => 'Matched',
            self::CANCELLED => 'Cancelled',
            self::EXPIRED => 'Expired',
        };
    }
}
