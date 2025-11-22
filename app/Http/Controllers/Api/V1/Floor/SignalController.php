<?php

namespace App\Http\Controllers\Api\V1\Floor;

use App\Actions\Client\ResolveClientIdAction;
use App\DataTransferObjects\Floor\SignalData;
use App\Enums\GameTitle;
use App\Exceptions\InvalidGameConfigurationException;
use App\Http\Requests\Floor\StoreSignalRequest;
use App\Http\Traits\ApiResponses;
use App\Matchmaking\Orchestrators\QuickplayOrchestrator;
use App\Models\MatchmakingSignal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class SignalController extends Controller
{
    use ApiResponses;

    public function __construct(
        protected ResolveClientIdAction $resolveClientId,
        protected QuickplayOrchestrator $quickplayOrchestrator
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
        $preferences = $validated['preferences'] ?? [];
        $skillRating = $validated['skill_rating'] ?? null;

        $result = $this->quickplayOrchestrator->joinQueue(
            $request->user(),
            $gameTitle,
            $gameMode,
            $clientId,
            $preferences,
            $skillRating
        );

        if (! $result->success) {
            $statusCode = $result->cooldownRemaining !== null ? 429 : 422;
            $errors = $result->context;

            // Add retry_after for cooldowns
            if ($result->cooldownRemaining !== null) {
                $errors['retry_after'] = $result->cooldownRemaining;
            }

            $response = $this->errorResponse(
                $result->errorMessage,
                $statusCode,
                null,
                $errors
            );

            if ($result->cooldownRemaining !== null) {
                $response->header('Retry-After', (string) $result->cooldownRemaining);
            }

            return $response;
        }

        return $this->createdResponse(
            SignalData::fromModel($result->signal)->toArray(),
            'Matchmaking signal created'
        );
    }

    public function destroy(Request $request, MatchmakingSignal $signal): JsonResponse
    {
        $user = $request->user();

        if ($signal->user_id !== $user->id) {
            return $this->forbiddenResponse('You can only cancel your own signal');
        }

        $result = $this->quickplayOrchestrator->cancelQueue($user);

        if (! $result->success) {
            return $this->errorResponse(
                $result->errorMessage,
                422,
                null,
                $result->context
            );
        }

        return $this->dataResponse(
            SignalData::fromModel($signal->fresh())->toArray(),
            'Matchmaking signal cancelled'
        );
    }
}
