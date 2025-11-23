<?php

namespace App\Http\Resources\Economy;

use App\Models\Economy\Balance;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Balance
 */
class BalanceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'currency_type' => $this->currency_type,
            'total' => $this->amount,
            'reserved' => $this->reserved_amount,
            'available' => $this->availableBalance,
        ];
    }
}
