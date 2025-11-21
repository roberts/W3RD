<?php

namespace App\Http\Controllers\Api\V1\Economy;

use App\DataTransferObjects\Economy\TransactionData;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponses;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    use ApiResponses;

    /**
     * Get user's transaction history.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $currencyType = $request->query('currency_type');
        $transactionType = $request->query('transaction_type');

        $query = Transaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc');

        if ($currencyType) {
            $query->where('currency_type', $currencyType);
        }

        if ($transactionType) {
            $query->where('transaction_type', $transactionType);
        }

        $transactions = $query->paginate(50);

        return $this->collectionResponse(
            $transactions,
            fn ($items) => $items->map(fn ($tx) => TransactionData::fromModel($tx))
        );
    }
}
