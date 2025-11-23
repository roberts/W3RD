<?php

namespace App\Http\Controllers\Api\V1\Competitions;

use App\DataTransferObjects\Competitions\StandingData;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponses;
use App\Models\Auth\User;
use App\Models\Competitions\Tournament;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StandingsController extends Controller
{
    use ApiResponses;

    /**
     * Get current standings for a tournament.
     */
    public function show(Request $request, string $tournamentUlid): JsonResponse
    {
        $tournament = Tournament::where('ulid', $tournamentUlid)
            ->with(['users.avatar.image'])
            ->firstOrFail();

        /** @var \Illuminate\Database\Eloquent\Collection<int, User> $users */
        $users = $tournament->users()
            ->orderBy('tournament_user.placement', 'asc')
            ->orderBy('tournament_user.earnings', 'desc')
            ->get();

        $standings = $users->map(fn (User $user) => StandingData::fromUser($user, $tournament));

        return $this->dataResponse([
            'tournament_ulid' => $tournament->ulid,
            'standings' => $standings,
        ]);
    }
}
