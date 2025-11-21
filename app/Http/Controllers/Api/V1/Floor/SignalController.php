<?php

namespace App\Http\Controllers\Api\V1\Floor;

use App\Actions\Client\ResolveClientIdAction;
use App\Actions\Quickplay\JoinQuickplayQueueAction;
use App\Actions\Quickplay\LeaveQuickplayQueueAction;
use App\DataTransferObjects\Floor\SignalData;
use App\Enums\GameTitle;
use App\Exceptions\CooldownActiveException;
use App\Exceptions\InvalidGameConfigurationException;
use App\Http\Requests\Floor\StoreSignalRequest;
use App\Http\Traits\ApiResponses;
use App\Models\MatchmakingSignal;
use App\Services\Floor\FloorCoordinationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class SignalController extends Controller
{
    use ApiResponses;

    public function __construct(
        protected ResolveClientIdAction $resolveClientId,
        protected JoinQuickplayQueueAction $joinQueue,
        protected LeaveQuickplayQueueAction $leaveQueue,
        protected FloorCoordinationService $floorService
    ) {
        $this->middleware('auth:sanctum');
    }

    public function store(StoreSignalRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $gameTitle = GameTitle::fromSlug($validated['game_title']);

        if (! $gameTitle) {
            throw new InvalidGameConfigurationException(
                "Game title '{$validated['game_title']}' is not supported",
                $validated['game_title'],
                ['available_titles' => array_column(GameTitle::cases(), 'value')]
            );
        }

        $gameMode = $validated['game_mode'] ?? 'standard';
        $clientId = $this->resolveClientId->execute($request);

        $result = $this->joinQueue->execute(
            $request->user(),
            $gameTitle,
            $gameMode,
            $clientId
        );

        if (! $result->success) {
            throw new CooldownActiveException(
                $result->errorMessage ?? 'Please wait before joining another game',
                'post_game',
                $result->cooldownRemaining,
                ['cooldown_remaining' => $result->cooldownRemaining]
            );
        }

        $signal = $this->floorService->createSignal(
            $request->user(),
            $gameTitle,
            $gameMode,
            $validated['preferences'] ?? [],
            $validated['skill_rating'] ?? null
        );

        return $this->createdResponse(
            SignalData::fromModel($signal)->toArray(),
            'Matchmaking signal created'
        );
    }

    public function destroy(Request $request, MatchmakingSignal $signal): JsonResponse
    {
        $user = $request->user();

        if ($signal->user_id !== $user->id) {
            return $this->forbiddenResponse('You can only cancel your own signal');
        }

        $this->leaveQueue->execute($user);
        $cancelled = $this->floorService->cancelSignal($signal);

        return $this->dataResponse(
            SignalData::fromModel($cancelled->fresh())->toArray(),
            'Matchmaking signal cancelled'
        );
    }
}
