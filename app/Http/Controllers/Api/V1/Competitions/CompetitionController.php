<?php

namespace App\Http\Controllers\Api\V1\Competitions;

use App\DataTransferObjects\Competitions\CompetitionData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Competitions\ListCompetitionsRequest;
use App\Http\Traits\ApiResponses;
use App\Models\Competitions\Tournament;
use App\Services\Competitions\TournamentQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompetitionController extends Controller
{
    use ApiResponses;

    public function __construct(
        protected TournamentQueryService $tournamentQueryService
    ) {}

    /**
     * List available tournaments/competitions.
     */
    public function index(ListCompetitionsRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $perPage = $validated['per_page'] ?? 20;
        $tournaments = $this->tournamentQueryService
            ->buildTournamentQuery($validated)
            ->orderBy('starts_at', 'asc')
            ->paginate($perPage);

        return $this->collectionResponse(
            $tournaments,
            fn ($items) => $items->map(fn ($t) => CompetitionData::fromModel($t))
        );
    }

    /**
     * Get details of a specific tournament.
     */
    public function show(Request $request, Tournament $tournament): JsonResponse
    {
        $tournament->load(['users.avatar.image']);

        return $this->dataResponse(CompetitionData::fromModel($tournament));
    }
}
