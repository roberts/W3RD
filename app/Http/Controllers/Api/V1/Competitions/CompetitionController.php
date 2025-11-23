<?php

namespace App\Http\Controllers\Api\V1\Competitions;

use App\DataTransferObjects\Competitions\CompetitionData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Competitions\ListCompetitionsRequest;
use App\Http\Traits\ApiResponses;
use App\Models\Competitions\Tournament;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompetitionController extends Controller
{
    use ApiResponses;

    /**
     * List available tournaments/competitions.
     */
    public function index(ListCompetitionsRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $query = Tournament::query()->with('users');

        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (isset($validated['game_title'])) {
            $query->where('game_title', $validated['game_title']);
        }

        if (isset($validated['format'])) {
            $query->where('format', $validated['format']);
        }

        $perPage = $validated['per_page'] ?? 20;
        $tournaments = $query->orderBy('starts_at', 'asc')->paginate($perPage);

        return $this->collectionResponse(
            $tournaments,
            fn ($items) => $items->map(fn ($t) => CompetitionData::fromModel($t))
        );
    }

    /**
     * Get details of a specific tournament.
     */
    public function show(Request $request, string $tournamentUlid): JsonResponse
    {
        $tournament = Tournament::where('ulid', $tournamentUlid)
            ->with(['users.avatar.image'])
            ->firstOrFail();

        return $this->dataResponse(CompetitionData::fromModel($tournament));
    }
}
