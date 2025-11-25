<?php

namespace App\Matchmaking\Enums;

enum LobbyStatus: string
{
    case PENDING = 'pending';
    case READY = 'ready';
    case CANCELLED = 'cancelled';
    case COMPLETED = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::READY => 'Ready',
            self::CANCELLED => 'Cancelled',
            self::COMPLETED => 'Completed',
        };
    }
}
