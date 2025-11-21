<?php

namespace App\Http\Controllers\Api\V1\Floor;

use App\Actions\User\ResolveUsernameAction;
use App\DataTransferObjects\Floor\ProposalData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Proposal\AcceptProposalRequest;
use App\Http\Requests\Proposal\DeclineProposalRequest;
use App\Http\Requests\Proposal\StoreProposalRequest;
use App\Http\Traits\ApiResponses;
use App\Models\Game\Game;
use App\Models\Game\Proposal;
use App\Services\Floor\FloorCoordinationService;
use App\Services\RematchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;

class ProposalController extends Controller
{
    use ApiResponses;

    public function __construct(
        protected FloorCoordinationService $floorService,
        protected ResolveUsernameAction $resolveUsername,
        protected RematchService $rematchService
    ) {}

    /**
     * Create a new proposal (rematch/challenge).
     */
    public function store(StoreProposalRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $type = Arr::get($validated, 'type', 'rematch');

        if ($type === 'rematch') {
            $game = Game::where('ulid', $validated['original_game_ulid'])
                ->firstOrFail();

            $proposal = $this->handleServiceCall(
                fn () => $this->rematchService->createRematchRequest(
                    $game,
                    $request->user()
                ),
                'Failed to create rematch request'
            );

            if ($proposal instanceof JsonResponse) {
                return $proposal;
            }

            return $this->createdResponse(
                ProposalData::fromModel($proposal),
                'Proposal sent successfully'
            );
        }

        $opponent = $this->resolveUsername->execute($validated['opponent_username']);

        $originalGameId = null;
        if (! empty($validated['original_game_ulid'])) {
            $originalGameId = optional(
                Game::where('ulid', $validated['original_game_ulid'])->first()
            )?->id;
        }

        $proposal = $this->floorService->createProposal(
            $request->user(),
            $opponent,
            [
                'title_slug' => $validated['title_slug'] ?? null,
                'mode_id' => $validated['mode_id'] ?? null,
                'type' => $type,
                'original_game_id' => $originalGameId,
                'game_settings' => $validated['game_settings'] ?? null,
            ]
        );

        return $this->createdResponse(
            ProposalData::fromModel($proposal),
            'Proposal sent successfully'
        );
    }

    /**
     * Accept a rematch request.
     */
    public function accept(AcceptProposalRequest $request, Proposal $proposal): JsonResponse
    {
        $newGame = $this->handleServiceCall(
            fn () => $this->rematchService->acceptRematchRequest(
                $proposal,
                $request->user()
            ),
            'Failed to accept rematch request'
        );

        if ($newGame instanceof JsonResponse) {
            return $newGame;
        }

        $resourceData = ProposalData::fromModel($proposal->fresh())->toArray();
        $resourceData['new_game_ulid'] = $newGame->ulid;

        return $this->dataResponse($resourceData, 'Proposal accepted. New game created.');
    }

    /**
     * Decline a rematch request.
     */
    public function decline(DeclineProposalRequest $request, Proposal $proposal): JsonResponse
    {
        $result = $this->handleServiceCall(
            function () use ($proposal, $request) {
                $this->rematchService->declineRematchRequest(
                    $proposal,
                    $request->user()
                );

                return true;
            },
            'Failed to decline rematch request',
            403
        );

        if ($result instanceof JsonResponse) {
            return $result;
        }

        return $this->dataResponse(
            ProposalData::fromModel($proposal->fresh()),
            'Rematch request declined'
        );
    }
}
