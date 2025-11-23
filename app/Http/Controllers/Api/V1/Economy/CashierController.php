<?php

namespace App\Http\Controllers\Api\V1\Economy;

use App\Http\Controllers\Controller;
use App\Http\Requests\Economy\AdjustBalanceRequest;
use App\Http\Traits\ApiResponses;
use App\Services\Economy\EconomyService;
use Illuminate\Http\JsonResponse;

class CashierController extends Controller
{
    use ApiResponses;

    public function __construct(
        protected EconomyService $economyService
    ) {}

    /**
     * Adjust user balance (approved clients only).
     * This endpoint is for entertainment chips/tokens, not real money.
     */
    public function store(AdjustBalanceRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $transaction = $this->handleServiceCall(
            fn () => $this->economyService->adjustBalance(
                userId: $validated['user_id'],
                currencyType: $validated['currency_type'],
                amount: $validated['amount'],
                description: $validated['description'] ?? 'Balance adjustment',
                metadata: $validated['metadata'] ?? []
            ),
            'Failed to adjust balance'
        );

        if ($transaction instanceof JsonResponse) {
            return $transaction;
        }

        return $this->createdResponse(
            [
                'transaction_ulid' => $transaction->ulid,
                'new_balance' => $transaction->user->balances()
                    ->forCurrency($validated['currency_type'])
                    ->first()->amount ?? 0,
            ],
            'Balance adjusted successfully'
        );
    }
}
