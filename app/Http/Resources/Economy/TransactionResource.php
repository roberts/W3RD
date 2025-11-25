<?php

namespace App\Http\Resources\Economy;

use App\Models\Economy\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Transaction
 */
class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'ulid' => $this->ulid,
            'transaction_type' => $this->transaction_type,
            'currency_type' => $this->currency_type,
            'amount' => $this->amount,
            'description' => $this->description,
            'metadata' => $this->metadata,
            'reference_id' => $this->reference_id,
            'reference_type' => $this->reference_type,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
