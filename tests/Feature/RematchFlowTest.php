<?php

use App\Enums\GameStatus;
use App\Enums\PlayerActivityState;
use App\Events\GameCompleted;
use App\Events\RematchAccepted;
use App\Jobs\AgentAutoAcceptRematch;
use App\Models\Auth\Agent;
use App\Models\Auth\User;
use App\Models\Game\Game;
use App\Models\Game\RematchRequest;
use App\Services\PlayerActivityService;
use App\Services\RematchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;

uses(RefreshDatabase::class);

describe('Rematch Flow with Agents', function () {
    beforeEach(function () {
        Event::fake([GameCompleted::class, RematchAccepted::class]);
        Queue::fake();

        // Mock Redis operations - default returns
        Redis::shouldReceive('exists')->andReturn(false)->byDefault();
        Redis::shouldReceive('hgetall')->andReturn([])->byDefault();
        Redis::shouldReceive('hmset')->andReturn(true)->byDefault();
        Redis::shouldReceive('del')->andReturn(1)->byDefault();
        Redis::shouldReceive('setex')->andReturn(true)->byDefault();
        Redis::shouldReceive('get')->andReturn(null)->byDefault();
        Redis::shouldReceive('expire')->andReturn(true)->byDefault();

        $this->activityService = app(PlayerActivityService::class);
        $this->rematchService = app(RematchService::class);
    });

    it('completes full rematch flow: game → request → auto-accept → new game', function () {
        // Create agent and human users
        $humanUser = User::factory()->create();
        $game = Game::factory()->completed()->withAgentOpponent($humanUser)->create(['creator_id' => $humanUser->id]);

        $agentUser = $game->agent_user;
        $agent = $game->agent;

        // Mock Redis for cooldown and activity states
        $cooldownKey = "agent:{$agentUser->id}:cooldown";
        Redis::shouldReceive('exists')->with($cooldownKey)->andReturn(true);
        Redis::shouldReceive('hgetall')->with($cooldownKey)->andReturn([
            'game_id' => (string) $game->id,
            'human_user_id' => (string) $humanUser->id,
        ]);
        Redis::shouldReceive('get')->with("player:{$humanUser->id}:activity")->andReturn('idle');
        Redis::shouldReceive('get')->with("player:{$agentUser->id}:activity")->andReturn('idle');

        // Dispatch GameCompleted event
        event(new GameCompleted($game, $humanUser->ulid, false));

        // Verify both players set to IDLE
        expect($this->activityService->getState($humanUser->id))->toBe(PlayerActivityState::IDLE)
            ->and($this->activityService->getState($agentUser->id))->toBe(PlayerActivityState::IDLE);

        // Human requests rematch
        $rematchRequest = $this->rematchService->createRematchRequest($game, $humanUser);

        expect($rematchRequest->status)->toBe('pending')
            ->and($rematchRequest->requesting_user_id)->toBe($humanUser->id)
            ->and($rematchRequest->opponent_user_id)->toBe($agentUser->id);

        // Verify auto-accept job dispatched with delay
        Queue::assertPushed(AgentAutoAcceptRematch::class, function ($job) use ($agentUser, $rematchRequest) {
            return $job->agentUserId === $agentUser->id
                && $job->rematchRequestId === $rematchRequest->ulid
                && $job->delay !== null;
        });

        // Execute the job immediately (simulating delay elapsed)
        $job = new AgentAutoAcceptRematch($rematchRequest->ulid, $agentUser->id);
        $job->handle();

        // Verify rematch accepted and new game created
        $rematchRequest->refresh();
        expect($rematchRequest->status)->toBe('accepted')
            ->and($rematchRequest->new_game_id)->not->toBeNull();

        $newGame = Game::find($rematchRequest->new_game_id);
        expect($newGame)->not->toBeNull()
            ->and($newGame->status)->toBe(GameStatus::PENDING)
            ->and($newGame->title_slug)->toBe($game->title_slug)
            ->and($newGame->mode_id)->toBe($game->mode_id);

        // Verify players swapped positions
        $newPlayers = $newGame->players;
        expect($newPlayers)->toHaveCount(2);

        $newHumanPlayer = $newPlayers->firstWhere('user_id', $humanUser->id);
        $newAgentPlayer = $newPlayers->firstWhere('user_id', $agentUser->id);

        expect($newHumanPlayer->position_id)->toBe(2) // Was 1, now 2
            ->and($newAgentPlayer->position_id)->toBe(1); // Was 2, now 1
    });

    it('auto-accepts rematch only when both players are IDLE', function () {
        $humanUser = User::factory()->create();
        $game = Game::factory()->completed()->withAgentOpponent($humanUser)->create();

        $agentUser = $game->agent_user;

        // Create rematch request directly without validation (testing job behavior)
        $rematchRequest = RematchRequest::create([
            'original_game_id' => $game->id,
            'requesting_user_id' => $humanUser->id,
            'opponent_user_id' => $agentUser->id,
            'status' => 'pending',
            'expires_at' => now()->addMinutes(5),
        ]);

        // Mock state checks: agent IDLE, human IN_QUEUE (simulate state changed during delay)
        Redis::shouldReceive('get')
            ->with("player:{$agentUser->id}:activity")
            ->andReturn('idle');
        Redis::shouldReceive('get')
            ->with("player:{$humanUser->id}:activity")
            ->andReturn('in_queue');

        // Execute auto-accept job - should cancel because human is IN_QUEUE
        $job = new AgentAutoAcceptRematch($rematchRequest->ulid, $agentUser->id);
        $job->handle();

        // Verify rematch cancelled, not accepted
        $rematchRequest->refresh();
        expect($rematchRequest->status)->toBe('cancelled');
    });

    it('rejects rematch request if agent cooldown expired', function () {
        $humanUser = User::factory()->create();
        $game = Game::factory()->completed()->withAgentOpponent($humanUser, 'alwaysAvailable')->create();
        $agentUser = $game->agent_user;

        // Mock no cooldown
        $cooldownKey = "agent:{$agentUser->id}:cooldown";
        Redis::shouldReceive('exists')->with($cooldownKey)->andReturn(false);
        Redis::shouldReceive('get')->with("player:{$humanUser->id}:activity")->andReturn('idle');
        Redis::shouldReceive('get')->with("player:{$agentUser->id}:activity")->andReturn('idle');

        // Set both to IDLE but no cooldown
        $this->activityService->setState($humanUser->id, PlayerActivityState::IDLE);
        $this->activityService->setState($agentUser->id, PlayerActivityState::IDLE);

        // Try to create rematch without cooldown (should fail)
        expect(fn () => $this->rematchService->createRematchRequest($game, $humanUser))
            ->toThrow(\InvalidArgumentException::class, 'no longer available');
    });

    it('cancels auto-accept if agent becomes unavailable', function () {
        $humanUser = User::factory()->create();
        $game = Game::factory()->completed()->withAgentOpponent($humanUser)->create();

        $agentUser = $game->agent_user;

        // Mock cooldown
        $cooldownKey = "agent:{$agentUser->id}:cooldown";
        Redis::shouldReceive('exists')->with($cooldownKey)->andReturn(true);
        Redis::shouldReceive('hgetall')->with($cooldownKey)->andReturn([
            'game_id' => (string) $game->id,
            'human_user_id' => (string) $humanUser->id,
        ]);
        Redis::shouldReceive('get')->with("player:{$humanUser->id}:activity")->andReturn('idle');
        Redis::shouldReceive('get')->with("player:{$agentUser->id}:activity")->andReturn('idle', 'in_game');

        event(new GameCompleted($game, $humanUser->ulid, false));
        $rematchRequest = $this->rematchService->createRematchRequest($game, $humanUser);

        // Agent joins another game
        $this->activityService->setState($agentUser->id, PlayerActivityState::IN_GAME);

        $job = new AgentAutoAcceptRematch($rematchRequest->ulid, $agentUser->id);
        $job->handle();

        $rematchRequest->refresh();
        expect($rematchRequest->status)->toBe('cancelled');
    });
});
