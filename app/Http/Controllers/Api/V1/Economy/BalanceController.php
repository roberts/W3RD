<?php

namespace App\Http\Controllers\Api\V1\Economy;

use App\Http\Controllers\Controller;
use App\Http\Resources\Economy\BalanceResource;
use App\Http\Traits\ApiResponses;
use App\Models\Economy\Balance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BalanceController extends Controller
{
    use ApiResponses;

    /**
     * Get user's balance for all currency types.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $balances = Balance::where('user_id', $user->id)->get();

        $balanceData = $balances->mapWithKeys(function ($balance) {
            $resource = BalanceResource::make($balance)->resolve();

            return [$balance->currency_type => $resource];
        });

        return $this->dataResponse($balanceData->toArray());
    }
}
