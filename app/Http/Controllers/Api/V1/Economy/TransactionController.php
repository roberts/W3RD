<?php

namespace App\Http\Controllers\Api\V1\Economy;

use App\Http\Controllers\Controller;
use App\Http\Requests\Economy\ListTransactionsRequest;
use App\Http\Resources\Economy\TransactionResource;
use App\Http\Traits\ApiResponses;
use App\Models\Economy\Transaction;
use Illuminate\Http\JsonResponse;

class TransactionController extends Controller
{
    use ApiResponses;

    /**
     * Get user's transaction history.
     */
    public function index(ListTransactionsRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $query = Transaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc');

        // Apply filters
        if (isset($validated['currency'])) {
            $query->forCurrency($validated['currency']);
        }

        if (isset($validated['type'])) {
            $query->where('transaction_type', $validated['type']);
        }

        if (isset($validated['date_from'])) {
            $query->where('created_at', '>=', $validated['date_from']);
        }

        if (isset($validated['date_to'])) {
            $query->where('created_at', '<=', $validated['date_to']);
        }

        if (isset($validated['min_amount'])) {
            $query->where('amount', '>=', $validated['min_amount']);
        }

        if (isset($validated['max_amount'])) {
            $query->where('amount', '<=', $validated['max_amount']);
        }

        $perPage = $validated['per_page'] ?? 50;
        $transactions = $query->paginate($perPage);

        return $this->collectionResponse(
            $transactions,
            fn ($items) => TransactionResource::collection($items)
        );
    }
}
