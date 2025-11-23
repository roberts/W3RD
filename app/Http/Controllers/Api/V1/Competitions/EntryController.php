<?php

namespace App\Http\Controllers\Api\V1\Competitions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Competitions\EnterTournamentRequest;
use App\Http\Traits\ApiResponses;
use App\Models\Competitions\Tournament;
use App\Services\Competitions\CompetitionService;
use Illuminate\Http\JsonResponse;

class EntryController extends Controller
{
    use ApiResponses;

    public function __construct(
        protected CompetitionService $competitionService
    ) {}

    /**
     * Enter a tournament.
     */
    public function store(EnterTournamentRequest $request, Tournament $tournament): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

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
