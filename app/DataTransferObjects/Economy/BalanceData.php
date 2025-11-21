<?php

namespace App\DataTransferObjects\Economy;

use App\Models\Balance;
use Spatie\LaravelData\Data;

class BalanceData extends Data
{
    public function __construct(
        public int $user_id,
        public string $currency_type,
        public int $total,
        public int $reserved,
        public int $available,
        public string $updated_at,
    ) {}

    public static function fromModel(Balance $balance): self
    {
        return new self(
            user_id: $balance->user_id,
            currency_type: $balance->currency_type,
            total: $balance->amount,
            reserved: $balance->reserved_amount,
            available: $balance->availableBalance,
            updated_at: $balance->updated_at->toIso8601String(),
        );
    }
}
