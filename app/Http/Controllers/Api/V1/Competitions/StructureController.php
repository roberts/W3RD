<?php

namespace App\Http\Controllers\Api\V1\Competitions;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponses;
use App\Models\Competitions\Tournament;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StructureController extends Controller
{
    use ApiResponses;

    /**
     * Get tournament structure (format, rules, prize distribution).
     */
    public function show(Request $request, string $tournamentUlid): JsonResponse
    {
        $tournament = Tournament::where('ulid', $tournamentUlid)->firstOrFail();

        $structureData = [
            'format' => $tournament->format,
            'max_participants' => $tournament->max_participants,
            'current_participants' => $tournament->users()->count(),
            'buy_in' => [
                'amount' => $tournament->buy_in_amount,
                'currency' => $tournament->buy_in_currency,
            ],
            'prize_pool' => $tournament->prize_pool,
            'rules' => $tournament->rules ?? [],
            'starts_at' => $tournament->starts_at?->toIso8601String(),
            'ends_at' => $tournament->ends_at?->toIso8601String(),
        ];

        return $this->dataResponse($structureData);
    }
}
