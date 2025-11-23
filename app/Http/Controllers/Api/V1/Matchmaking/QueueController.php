<?php

namespace App\Http\Controllers\Api\V1\Matchmaking;

use App\Actions\Client\ResolveClientIdAction;
use App\DataTransferObjects\Matchmaking\QueueSlotData;
use App\Enums\GameTitle;
use App\Exceptions\InvalidGameConfigurationException;
use App\Http\Requests\Matchmaking\CancelQueueRequest;
use App\Http\Requests\Matchmaking\StoreQueueRequest;
use App\Http\Traits\ApiResponses;
use App\Matchmaking\Orchestrators\QueueOrchestrator;
use App\Models\Games\Mode;
use App\Models\Matchmaking\QueueSlot;
use App\Services\Matchmaking\QueueResponseMapper;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class QueueController extends Controller
{
    use ApiResponses;

    public function __construct(
        protected ResolveClientIdAction $resolveClientId,
        protected QueueOrchestrator $queueOrchestrator,
        protected QueueResponseMapper $responseMapper
    ) {
        $this->middleware('auth:sanctum');
    }

    public function store(StoreQueueRequest $request): JsonResponse
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

        // Get the mode from the database
        $mode = Mode::findOrFail($validated['mode_id']);

        $clientId = $this->resolveClientId->execute($request);
        $preferences = $validated['preferences'] ?? [];
        $skillRating = $validated['skill_rating'] ?? null;

        $result = $this->queueOrchestrator->joinQueue(
            $request->user(),
            $gameTitle,
            $mode->slug,
            $mode->id,
            $clientId,
            $preferences,
            $skillRating
        );

        if (! $result->success) {
            return $this->responseMapper->mapJoinResult($result);
        }

        return $this->createdResponse(
            QueueSlotData::fromModel($result->slot)->toArray(),
            'Queue slot created'
        );
    }

    public function destroy(CancelQueueRequest $request, QueueSlot $slot): JsonResponse
    {
        $user = $request->user();

        $result = $this->queueOrchestrator->cancelQueue($user);

        if (! $result->success) {
            return $this->responseMapper->mapCancelResult($result);
        }

        return $this->dataResponse(
            QueueSlotData::fromModel($slot->fresh())->toArray(),
            'Queue slot cancelled'
        );
    }
}
