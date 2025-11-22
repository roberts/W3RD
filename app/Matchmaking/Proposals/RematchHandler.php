<?php

declare(strict_types=1);

namespace App\Matchmaking\Proposals;

use App\Enums\GameStatus;
use App\Exceptions\RematchNotAvailableException;
use App\Jobs\AgentAutoAcceptRematch;
use App\Matchmaking\Enums\ProposalStatus;
use App\Matchmaking\Enums\ProposalType;
use App\Matchmaking\Events\ProposalAccepted;
use App\Matchmaking\Events\ProposalCreated;
use App\Matchmaking\Events\ProposalDeclined;
use App\Matchmaking\Results\ProposalResult;
use App\Models\Auth\User;
use App\Models\Game\Game;
use App\Models\Matchmaking\Proposal;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Handles rematch proposal creation and acceptance.
 * Extracted from RematchService.
 */
class RematchHandler implements ProposalHandler
{
    public function __construct(
        private RematchValidator $validator
    ) {}

    public function supports(string $type): bool
    {
        return $type === 'rematch';
    }

    /**
     * Create a rematch request.
     */
    public function create(Game $game, User $requestingUser, ?User $opponentUser = null): ProposalResult
    {
        try {
            $opponent = $this->validator->validateRematchRequest($game, $requestingUser);

            $expirationMinutes = config('protocol.floor.proposals.expiration_minutes', 5);

            $proposal = Proposal::create([
                'original_game_id' => $game->id,
                'requesting_user_id' => $requestingUser->id,
                'opponent_user_id' => $opponent->user_id,
                'title_slug' => $game->title_slug->value,
                'mode_id' => $game->mode_id,
                'type' => ProposalType::REMATCH,
                'game_settings' => $game->game_settings,
                'status' => ProposalStatus::PENDING,
                'expires_at' => Carbon::now()->addMinutes($expirationMinutes),
            ]);

            // If opponent is agent within cooldown window, schedule auto-accept
            $opponentUser = User::find($opponent->user_id);
            if ($opponentUser && $opponentUser->isAgent()) {
                $this->scheduleAgentAutoAccept($proposal, $opponentUser, $requestingUser);
            }

            event(new ProposalCreated($proposal));

            return ProposalResult::success($proposal);
        } catch (RematchNotAvailableException $e) {
            return ProposalResult::failed($e->getMessage());
        }
    }

    /**
     * Accept a rematch request and create a new game.
     */
    public function accept(Proposal $proposal, User $acceptingUser, bool $isAutoAccept = false): ProposalResult
    {
        try {
            $this->validator->validateAcceptance($proposal, $acceptingUser, $isAutoAccept);

            $newGame = DB::transaction(function () use ($proposal) {
                /** @var Game $originalGame */
                $originalGame = $proposal->originalGame;

                // Initialize game state based on game title
                $gameState = $this->initializeGameState($originalGame);

                // Create new game with same settings
                $newGame = Game::create([
                    'title_slug' => $originalGame->title_slug,
                    'mode_id' => $originalGame->mode_id,
                    'creator_id' => $originalGame->creator_id,
                    'status' => GameStatus::PENDING,
                    'game_state' => $gameState,
                ]);

                // Copy players to new game (swap positions for fairness)
                foreach ($originalGame->players as $player) {
                    $newGame->players()->create([
                        'user_id' => $player->user_id,
                        'client_id' => $player->client_id,
                        'color' => $player->color,
                        'position_id' => $player->position_id === 1 ? 2 : 1, // Swap positions
                    ]);
                }

                // Update proposal
                $proposal->update([
                    'status' => 'accepted',
                    'game_id' => $newGame->id,
                    'responded_at' => now(),
                ]);

                event(new ProposalAccepted($proposal, $newGame));

                return $newGame;
            });

            return ProposalResult::success($proposal, $newGame);
        } catch (RematchNotAvailableException $e) {
            return ProposalResult::failed($e->getMessage());
        }
    }

    /**
     * Decline a rematch request.
     */
    public function decline(Proposal $proposal, User $decliningUser): ProposalResult
    {
        try {
            // Validate user is the opponent
            if ($proposal->opponent_user_id !== $decliningUser->id) {
                throw new AccessDeniedHttpException('Only the opponent can decline this rematch request.');
            }

            // Validate request is still pending
            if ($proposal->status !== ProposalStatus::PENDING) {
                throw new RematchNotAvailableException('This rematch request is no longer pending.');
            }

            $proposal->update([
                'status' => ProposalStatus::DECLINED,
                'responded_at' => now(),
            ]);

            event(new ProposalDeclined($proposal));

            return ProposalResult::success($proposal);
        } catch (\Exception $e) {
            return ProposalResult::failed($e->getMessage());
        }
    }

    /**
     * Initialize game state for a rematch with swapped positions.
     */
    private function initializeGameState(Game $originalGame): array
    {
        $players = $originalGame->players;
        $player1 = $players->firstWhere('position_id', 1);
        $player2 = $players->firstWhere('position_id', 2);

        if ($originalGame->title_slug->value === 'connect-four') {
            return [
                'board' => array_fill(0, 6, array_fill(0, 7, null)),
                'current_player_ulid' => $player2->ulid, // Swapped - was player2, now position 1
                'columns' => 7,
                'rows' => 6,
                'connect_count' => 4,
                'players' => [
                    $player2->ulid => ['ulid' => $player2->ulid, 'position' => 1, 'color' => 'red'],
                    $player1->ulid => ['ulid' => $player1->ulid, 'position' => 2, 'color' => 'yellow'],
                ],
                'phase' => 'active',
                'status' => 'pending',
            ];
        }

        return [];
    }

    /**
     * Schedule auto-accept for agent rematches.
     */
    private function scheduleAgentAutoAccept(Proposal $proposal, User $agentUser, User $requestingUser): void
    {
        $cooldownKey = "agent:{$agentUser->id}:cooldown";
        $cooldownData = Redis::hgetall($cooldownKey);

        if (! empty($cooldownData) &&
            (int) $cooldownData['human_user_id'] === $requestingUser->id) {

            // Schedule delayed auto-accept (1-7 seconds random)
            $delay = rand(1, 7);

            dispatch(new AgentAutoAcceptRematch($proposal->ulid, $agentUser->id, app(\App\GameEngine\Player\PlayerActivityManager::class)))
                ->delay(now()->addSeconds($delay));

            Log::info('Scheduled agent auto-accept', [
                'rematch_request_id' => $proposal->ulid,
                'agent_id' => $agentUser->id,
                'delay_seconds' => $delay,
            ]);
        }
    }
}
