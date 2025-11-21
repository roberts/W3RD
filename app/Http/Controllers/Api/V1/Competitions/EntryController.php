<?php

namespace App\Http\Controllers\Api\V1\Competitions;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponses;
use App\Models\Tournament;
use App\Services\CompetitionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EntryController extends Controller
{
    use ApiResponses;

    public function __construct(
        protected CompetitionService $competitionService
    ) {}

    /**
     * Enter a tournament.
     */
    public function store(Request $request, string $tournamentUlid): JsonResponse
    {
        $tournament = Tournament::where('ulid', $tournamentUlid)->firstOrFail();
        $user = $request->user();

        $result = $this->handleServiceCall(
            fn () => $this->competitionService->enterTournament($tournament, $user),
            'Failed to enter tournament'
        );

        if ($result instanceof JsonResponse) {
            return $result;
        }

        return $this->createdResponse(
            ['tournament_ulid' => $tournament->ulid],
            'Successfully entered tournament'
        );
    }
}
