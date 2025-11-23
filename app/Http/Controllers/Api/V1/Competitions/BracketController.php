<?php

namespace App\Http\Controllers\Api\V1\Competitions;

use App\DataTransferObjects\Competitions\BracketData;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponses;
use App\Models\Competitions\Tournament;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BracketController extends Controller
{
    use ApiResponses;

    /**
     * Get tournament bracket and matchup progression.
     */
    public function show(Request $request, string $tournamentUlid): JsonResponse
    {
        $tournament = Tournament::where('ulid', $tournamentUlid)
            ->with(['users', 'games.players.user'])
            ->firstOrFail();

        if (! $tournament->hasStarted()) {
            return $this->errorResponse('Tournament has not started yet', 400);
        }

        $bracketData = BracketData::fromTournament($tournament);

        return $this->dataResponse($bracketData);
    }
}
