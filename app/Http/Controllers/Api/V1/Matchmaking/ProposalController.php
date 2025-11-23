<?php

namespace App\Http\Controllers\Api\V1\Matchmaking;

use App\Actions\User\ResolveUsernameAction;
use App\DataTransferObjects\Matchmaking\ProposalData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Proposal\AcceptProposalRequest;
use App\Http\Requests\Proposal\DeclineProposalRequest;
use App\Http\Requests\Proposal\StoreProposalRequest;
use App\Http\Traits\ApiResponses;
use App\Matchmaking\Orchestrators\ProposalOrchestrator;
use App\Models\Games\Game;
use App\Models\Matchmaking\Proposal;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;

class ProposalController extends Controller
{
    use ApiResponses;

    public function __construct(
        protected ProposalOrchestrator $proposalOrchestrator,
        protected ResolveUsernameAction $resolveUsername
    ) {}

    /**
     * Create a new proposal (rematch/challenge).
     */
    public function store(StoreProposalRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $type = Arr::get($validated, 'type', 'rematch');

        $game = Game::where('ulid', $validated['original_game_ulid'])->firstOrFail();

        $opponent = null;
        if (! empty($validated['opponent_username'])) {
            $opponent = $this->resolveUsername->execute($validated['opponent_username']);
        }

        $result = $this->proposalOrchestrator->createProposal(
            $type,
            $game,
            $request->user(),
            $opponent
        );

        if (! $result->success) {
            return $this->errorResponse($result->errorMessage, 422, null, $result->context);
        }

        return $this->createdResponse(
            ProposalData::fromModel($result->proposal),
            'Proposal sent successfully'
        );
    }

    /**
     * Accept a proposal.
     */
    public function accept(AcceptProposalRequest $request, Proposal $proposal): JsonResponse
    {
        $result = $this->proposalOrchestrator->acceptProposal(
            $proposal,
            $request->user()
        );

        if (! $result->success) {
            return $this->errorResponse($result->errorMessage, 422, null, $result->context);
        }

        $resourceData = ProposalData::fromModel($result->proposal)->toArray();
        if ($result->game) {
            $resourceData['new_game_ulid'] = $result->game->ulid;
        }

        return $this->dataResponse($resourceData, 'Proposal accepted. New game created.');
    }

    /**
     * Decline a proposal.
     */
    public function decline(DeclineProposalRequest $request, Proposal $proposal): JsonResponse
    {
        $result = $this->proposalOrchestrator->declineProposal(
            $proposal,
            $request->user()
        );

        if (! $result->success) {
            return $this->errorResponse($result->errorMessage, 403, null, $result->context);
        }

        return $this->dataResponse(
            ProposalData::fromModel($result->proposal),
            'Rematch request declined'
        );
    }
}
