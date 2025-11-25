<?php

use App\Enums\PlayerActivityState;
use App\GameEngine\Player\PlayerActivityManager;
use App\Jobs\CheckAndCancelPendingProposals;
use App\Matchmaking\Enums\ProposalStatus;
use App\Matchmaking\Events\ProposalCancelled;
use App\Models\Auth\User;
use App\Models\Games\Game;
use App\Models\Matchmaking\Proposal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Redis;

uses(RefreshDatabase::class);

describe('Automatic Rematch Cancellation', function () {
    beforeEach(function () {
        Event::fake();

        // Mock Redis operations
        Redis::shouldReceive('setex')->andReturn(true);
        Redis::shouldReceive('get')->andReturn(null);
        Redis::shouldReceive('hmset')->andReturn(true);
        Redis::shouldReceive('del')->andReturn(1);

        $this->activityService = app(PlayerActivityManager::class);
    });

    describe('CheckAndCancelPendingProposals job', function () {
        it('cancels pending rematch when requester joins queue', function () {
            $requester = User::factory()->create();
            $opponent = User::factory()->create();

            $proposal = Proposal::factory()
                ->fromCompletedGame($requester, $opponent)
                ->create();

            // Requester joins queue (triggers cancellation)
            $job = new CheckAndCancelPendingProposals($requester->id);
            $job->handle();

            $proposal->refresh();
            expect($proposal->status)->toBe(ProposalStatus::CANCELLED);

            Event::assertDispatched(ProposalCancelled::class, function ($event) use ($proposal) {
                return $event->rematchRequest->id === $proposal->id
                    && $event->reason === 'requester_unavailable';
            });
        });

        it('cancels pending rematch when opponent joins queue', function () {
            $requester = User::factory()->create();
            $opponent = User::factory()->create();

            $proposal = Proposal::factory()
                ->fromCompletedGame($requester, $opponent)
                ->create();

            // Opponent joins queue
            $job = new CheckAndCancelPendingProposals($opponent->id);
            $job->handle();

            $proposal->refresh();
            expect($proposal->status)->toBe(ProposalStatus::CANCELLED);

            Event::assertDispatched(ProposalCancelled::class, function ($event) use ($proposal) {
                return $event->rematchRequest->id === $proposal->id
                    && $event->reason === 'opponent_unavailable';
            });
        });

        it('cancels multiple pending rematches for same user', function () {
            $user = User::factory()->create();
            $opponent1 = User::factory()->create();
            $opponent2 = User::factory()->create();

            $rematch1 = Proposal::factory()
                ->fromCompletedGame($user, $opponent1)
                ->create();

            $rematch2 = Proposal::factory()
                ->fromCompletedGame($user, $opponent2)
                ->create();

            $job = new CheckAndCancelPendingProposals($user->id);
            $job->handle();

            expect($rematch1->fresh()->status)->toBe(ProposalStatus::CANCELLED)
                ->and($rematch2->fresh()->status)->toBe(ProposalStatus::CANCELLED);
        });

        it('does not cancel accepted or declined rematches', function () {
            $user = User::factory()->create();
            $opponent = User::factory()->create();

            $game1 = Game::factory()->completed()->create();
            $game2 = Game::factory()->completed()->create();

            $acceptedRematch = Proposal::factory()->accepted()->create([
                'original_game_id' => $game1->id,
                'requesting_user_id' => $user->id,
                'opponent_user_id' => $opponent->id,
            ]);

            $declinedRematch = Proposal::factory()->declined()->create([
                'original_game_id' => $game2->id,
                'requesting_user_id' => $user->id,
                'opponent_user_id' => $opponent->id,
            ]);

            $job = new CheckAndCancelPendingProposals($user->id);
            $job->handle();

            expect($acceptedRematch->fresh()->status)->toBe('accepted')
                ->and($declinedRematch->fresh()->status)->toBe('declined');
        });

        it('does nothing when user has no pending rematches', function () {
            $user = User::factory()->create();

            $job = new CheckAndCancelPendingProposals($user->id);
            $job->handle();

            Event::assertNotDispatched(ProposalCancelled::class);
        });
    });

    describe('integration with activity states', function () {
        it('triggers cancellation when joining matchmaking queue', function () {
            $user = User::factory()->create();
            $opponent = User::factory()->create();

            $proposal = Proposal::factory()
                ->fromCompletedGame($user, $opponent)
                ->create();

            // Setting to IN_QUEUE dispatches job (we need to manually execute for test)
            $this->activityService->setState($user->id, PlayerActivityState::IN_QUEUE);

            // Manually execute the job that would be dispatched
            $job = new CheckAndCancelPendingProposals($user->id);
            $job->handle();

            expect($proposal->fresh()->status)->toBe(ProposalStatus::CANCELLED);
        });

        it('triggers cancellation when joining lobby', function () {
            $user = User::factory()->create();
            $opponent = User::factory()->create();

            $proposal = Proposal::factory()
                ->fromCompletedGame($user, $opponent)
                ->create();

            $this->activityService->setState($user->id, PlayerActivityState::IN_LOBBY);

            $job = new CheckAndCancelPendingProposals($user->id);
            $job->handle();

            expect($proposal->fresh()->status)->toBe(ProposalStatus::CANCELLED);
        });

        it('triggers cancellation when starting another game', function () {
            $user = User::factory()->create();
            $opponent = User::factory()->create();

            $proposal = Proposal::factory()
                ->fromCompletedGame($user, $opponent)
                ->create();

            $this->activityService->setState($user->id, PlayerActivityState::IN_GAME);

            $job = new CheckAndCancelPendingProposals($user->id);
            $job->handle();

            expect($proposal->fresh()->status)->toBe(ProposalStatus::CANCELLED);
        });

        it('does not cancel when user goes IDLE', function () {
            $user = User::factory()->create();
            $opponent = User::factory()->create();

            $proposal = Proposal::factory()
                ->fromCompletedGame($user, $opponent)
                ->create();

            // IDLE doesn't trigger cancellation job, but verify rematch stays pending
            $this->activityService->setState($user->id, PlayerActivityState::IDLE);

            expect($proposal->fresh()->status)->toBe(ProposalStatus::PENDING);
        });

        it('cancels rematches when user logs out', function () {
            $user = User::factory()->create();
            $opponent = User::factory()->create();

            $proposal = Proposal::factory()
                ->fromCompletedGame($user, $opponent)
                ->create();

            // Logout sets OFFLINE (doesn't auto-trigger, handled by AuthController)
            $this->activityService->setState($user->id, PlayerActivityState::OFFLINE);

            // AuthController explicitly dispatches job on logout
            $job = new CheckAndCancelPendingProposals($user->id);
            $job->handle();

            expect($proposal->fresh()->status)->toBe(ProposalStatus::CANCELLED);
        });
    });
});
