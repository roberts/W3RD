<?php

use App\Enums\PlayerActivityState;
use App\Events\RematchCancelled;
use App\Jobs\CheckAndCancelPendingRematches;
use App\Models\Auth\User;
use App\Models\Game\Game;
use App\Models\Game\Player;
use App\Models\Game\RematchRequest;
use App\Services\PlayerActivityService;
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

        $this->activityService = app(PlayerActivityService::class);
    });

    describe('CheckAndCancelPendingRematches job', function () {
        it('cancels pending rematch when requester joins queue', function () {
            $requester = User::factory()->create();
            $opponent = User::factory()->create();

            $rematchRequest = RematchRequest::factory()
                ->fromCompletedGame($requester, $opponent)
                ->create();

            // Requester joins queue (triggers cancellation)
            $job = new CheckAndCancelPendingRematches($requester->id);
            $job->handle();

            $rematchRequest->refresh();
            expect($rematchRequest->status)->toBe('cancelled');

            Event::assertDispatched(RematchCancelled::class, function ($event) use ($rematchRequest) {
                return $event->rematchRequest->id === $rematchRequest->id
                    && $event->reason === 'requester_unavailable';
            });
        });

        it('cancels pending rematch when opponent joins queue', function () {
            $requester = User::factory()->create();
            $opponent = User::factory()->create();

            $rematchRequest = RematchRequest::factory()
                ->fromCompletedGame($requester, $opponent)
                ->create();

            // Opponent joins queue
            $job = new CheckAndCancelPendingRematches($opponent->id);
            $job->handle();

            $rematchRequest->refresh();
            expect($rematchRequest->status)->toBe('cancelled');

            Event::assertDispatched(RematchCancelled::class, function ($event) use ($rematchRequest) {
                return $event->rematchRequest->id === $rematchRequest->id
                    && $event->reason === 'opponent_unavailable';
            });
        });

        it('cancels multiple pending rematches for same user', function () {
            $user = User::factory()->create();
            $opponent1 = User::factory()->create();
            $opponent2 = User::factory()->create();

            $rematch1 = RematchRequest::factory()
                ->fromCompletedGame($user, $opponent1)
                ->create();

            $rematch2 = RematchRequest::factory()
                ->fromCompletedGame($user, $opponent2)
                ->create();

            $job = new CheckAndCancelPendingRematches($user->id);
            $job->handle();

            expect($rematch1->fresh()->status)->toBe('cancelled')
                ->and($rematch2->fresh()->status)->toBe('cancelled');
        });

        it('does not cancel accepted or declined rematches', function () {
            $user = User::factory()->create();
            $opponent = User::factory()->create();

            $game1 = Game::factory()->completed()->create();
            $game2 = Game::factory()->completed()->create();

            $acceptedRematch = RematchRequest::factory()->accepted()->create([
                'original_game_id' => $game1->id,
                'requesting_user_id' => $user->id,
                'opponent_user_id' => $opponent->id,
            ]);

            $declinedRematch = RematchRequest::factory()->declined()->create([
                'original_game_id' => $game2->id,
                'requesting_user_id' => $user->id,
                'opponent_user_id' => $opponent->id,
            ]);

            $job = new CheckAndCancelPendingRematches($user->id);
            $job->handle();

            expect($acceptedRematch->fresh()->status)->toBe('accepted')
                ->and($declinedRematch->fresh()->status)->toBe('declined');
        });

        it('does nothing when user has no pending rematches', function () {
            $user = User::factory()->create();

            $job = new CheckAndCancelPendingRematches($user->id);
            $job->handle();

            Event::assertNotDispatched(RematchCancelled::class);
        });
    });

    describe('integration with activity states', function () {
        it('triggers cancellation when joining quickplay queue', function () {
            $user = User::factory()->create();
            $opponent = User::factory()->create();

            $rematchRequest = RematchRequest::factory()
                ->fromCompletedGame($user, $opponent)
                ->create();

            // Setting to IN_QUEUE dispatches job (we need to manually execute for test)
            $this->activityService->setState($user->id, PlayerActivityState::IN_QUEUE);

            // Manually execute the job that would be dispatched
            $job = new CheckAndCancelPendingRematches($user->id);
            $job->handle();

            expect($rematchRequest->fresh()->status)->toBe('cancelled');
        });

        it('triggers cancellation when joining lobby', function () {
            $user = User::factory()->create();
            $opponent = User::factory()->create();

            $rematchRequest = RematchRequest::factory()
                ->fromCompletedGame($user, $opponent)
                ->create();

            $this->activityService->setState($user->id, PlayerActivityState::IN_LOBBY);

            $job = new CheckAndCancelPendingRematches($user->id);
            $job->handle();

            expect($rematchRequest->fresh()->status)->toBe('cancelled');
        });

        it('triggers cancellation when starting another game', function () {
            $user = User::factory()->create();
            $opponent = User::factory()->create();

            $rematchRequest = RematchRequest::factory()
                ->fromCompletedGame($user, $opponent)
                ->create();

            $this->activityService->setState($user->id, PlayerActivityState::IN_GAME);

            $job = new CheckAndCancelPendingRematches($user->id);
            $job->handle();

            expect($rematchRequest->fresh()->status)->toBe('cancelled');
        });

        it('does not cancel when user goes IDLE', function () {
            $user = User::factory()->create();
            $opponent = User::factory()->create();

            $rematchRequest = RematchRequest::factory()
                ->fromCompletedGame($user, $opponent)
                ->create();

            // IDLE doesn't trigger cancellation job, but verify rematch stays pending
            $this->activityService->setState($user->id, PlayerActivityState::IDLE);

            expect($rematchRequest->fresh()->status)->toBe('pending');
        });

        it('cancels rematches when user logs out', function () {
            $user = User::factory()->create();
            $opponent = User::factory()->create();

            $rematchRequest = RematchRequest::factory()
                ->fromCompletedGame($user, $opponent)
                ->create();

            // Logout sets OFFLINE (doesn't auto-trigger, handled by AuthController)
            $this->activityService->setState($user->id, PlayerActivityState::OFFLINE);

            // AuthController explicitly dispatches job on logout
            $job = new CheckAndCancelPendingRematches($user->id);
            $job->handle();

            expect($rematchRequest->fresh()->status)->toBe('cancelled');
        });
    });
});
