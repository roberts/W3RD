<?php

namespace App\Http\Controllers\Api\V1\Competitions;

use App\DataTransferObjects\Competitions\CompetitionData;
use App\Http\Controllers\Controller;
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
    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status');
        $gameTitle = $request->query('game_title');

        $query = Tournament::query()->with('users');

        if ($status) {
            $query->where('status', $status);
        }

        if ($gameTitle) {
            $query->where('game_title', $gameTitle);
        }

        $tournaments = $query->orderBy('starts_at', 'asc')->paginate(20);

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
