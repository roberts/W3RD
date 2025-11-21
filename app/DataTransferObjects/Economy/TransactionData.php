<?php

namespace App\DataTransferObjects\Economy;

use App\Models\Transaction;
use Spatie\LaravelData\Data;

class TransactionData extends Data
{
    public function __construct(
        public string $ulid,
        public int $user_id,
        public string $currency_type,
        public int $amount,
        public string $transaction_type,
        public string $description,
        public ?string $reference_type,
        public ?int $reference_id,
        public ?array $metadata,
        public string $created_at,
    ) {}

    public static function fromModel(Transaction $transaction): self
    {
        return new self(
            ulid: $transaction->ulid,
            user_id: $transaction->user_id,
            currency_type: $transaction->currency_type,
            amount: $transaction->amount,
            transaction_type: $transaction->transaction_type,
            description: $transaction->description,
            reference_type: $transaction->reference_type,
            reference_id: $transaction->reference_id,
            metadata: $transaction->metadata,
            created_at: $transaction->created_at->toIso8601String(),
        );
    }
}
