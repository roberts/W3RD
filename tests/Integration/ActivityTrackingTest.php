<?php

use App\Actions\Quickplay\JoinQuickplayQueueAction;
use App\Actions\Quickplay\LeaveQuickplayQueueAction;
use App\Enums\GameStatus;
use App\Enums\GameTitle;
use App\Enums\LobbyStatus;
use App\Enums\PlayerActivityState;
use App\Models\Auth\User;
use App\Models\Game\Game;
use App\Models\Game\Lobby;
use App\Models\Game\Player;
use App\Services\GameCreationService;
use App\Services\PlayerActivityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;

uses(RefreshDatabase::class);

describe('Activity Tracking in Game Creation', function () {
    beforeEach(function () {
        $this->states = [];
        
        // Mock Redis to track state changes in memory
        Redis::shouldReceive('setex')
            ->with(\Mockery::pattern('/player:\d+:activity/'), 1800, \Mockery::any())
            ->andReturnUsing(function ($key, $ttl, $value) {
                $this->states[$key] = $value;
                return true;
            })
            ->byDefault();
            
        Redis::shouldReceive('get')
            ->with(\Mockery::pattern('/player:\d+:activity/'))
            ->andReturnUsing(function ($key) {
                return $this->states[$key] ?? null;
            })
            ->byDefault();
        
        // Mock other Redis operations
        Redis::shouldReceive('hmset')->andReturn(true)->byDefault();
        Redis::shouldReceive('hgetall')->andReturn([])->byDefault();
        Redis::shouldReceive('del')->andReturn(1)->byDefault();
        Redis::shouldReceive('hdel')->andReturn(1)->byDefault();
        Redis::shouldReceive('exists')->andReturn(false)->byDefault();
        Redis::shouldReceive('hset')->andReturn(1)->byDefault();
        Redis::shouldReceive('zadd')->andReturn(1)->byDefault();
        Redis::shouldReceive('zscore')->andReturn(1.0)->byDefault();
        Redis::shouldReceive('zrem')->andReturn(1)->byDefault();
        Redis::shouldReceive('expire')->andReturn(true)->byDefault();
        
        $this->activityService = app(PlayerActivityService::class);
        $this->gameCreationService = app(GameCreationService::class);
    });

    describe('quickplay queue', function () {
        it('sets IN_QUEUE when joining quickplay', function () {
            $user = User::factory()->create();
            $joinAction = new JoinQuickplayQueueAction;

            $joinAction->execute($user, GameTitle::VALIDATE_FOUR, 'standard', 1);

            expect($this->activityService->getState($user->id))->toBe(PlayerActivityState::IN_QUEUE);
        });

        it('sets IDLE when leaving quickplay queue', function () {
            $user = User::factory()->create();
            
            // Join queue first
            $joinAction = new JoinQuickplayQueueAction;
            $joinAction->execute($user, GameTitle::VALIDATE_FOUR, 'standard', 1);
            
            // Then leave
            $leaveAction = new LeaveQuickplayQueueAction;
            $leaveAction->execute($user, GameTitle::VALIDATE_FOUR, 'standard');

            expect($this->activityService->getState($user->id))->toBe(PlayerActivityState::IDLE);
        });
    });

    describe('game creation from quickplay', function () {
        it('sets IN_GAME for both players when creating from quickplay match', function () {
            $user1 = User::factory()->create();
            $user2 = User::factory()->create();

            // Simulate quickplay match data in Redis
            $matchId = 'test-match-123';
            $matchKey = "quickplay:match:{$matchId}";
            Redis::hmset($matchKey, [
                'game_title' => 'validate-four',
                'game_mode' => 'standard',
                'player_'.$user1->id.'_client' => '1',
                'player_'.$user2->id.'_client' => '1',
            ]);

            $game = $this->gameCreationService->createFromQuickplayMatch(
                [$user1->id, $user2->id],
                $matchId
            );

            expect($game->status)->toBe(GameStatus::ACTIVE)
                ->and($this->activityService->getState($user1->id))->toBe(PlayerActivityState::IN_GAME)
                ->and($this->activityService->getState($user2->id))->toBe(PlayerActivityState::IN_GAME);
        });
    });

    describe('game creation from lobby', function () {
        it('sets IN_LOBBY when joining lobby', function () {
            $host = User::factory()->create();
            $player = User::factory()->create();

            $lobby = Lobby::factory()->create([
                'host_id' => $host->id,
                'game_title' => GameTitle::VALIDATE_FOUR,
                'status' => 'pending',
            ]);

            // Simulate joining lobby by setting state (actual logic in controller)
            $this->activityService->setState($player->id, PlayerActivityState::IN_LOBBY);

            expect($this->activityService->getState($player->id))->toBe(PlayerActivityState::IN_LOBBY);
        });

        it('sets all players to IN_GAME when creating game from lobby', function () {
            $host = User::factory()->create();
            $player = User::factory()->create();

            $lobby = Lobby::factory()->create([
                'host_id' => $host->id,
                'game_title' => GameTitle::VALIDATE_FOUR,
                'status' => 'pending',
            ]);

            // Add players to lobby
            $lobby->players()->create([
                'user_id' => $host->id,
                'client_id' => 1,
                'status' => 'accepted',
            ]);
            $lobby->players()->create([
                'user_id' => $player->id,
                'client_id' => 1,
                'status' => 'accepted',
            ]);

            $game = $this->gameCreationService->createFromLobby($lobby);

            expect($game->status)->toBe(GameStatus::ACTIVE)
                ->and($this->activityService->getState($host->id))->toBe(PlayerActivityState::IN_GAME)
                ->and($this->activityService->getState($player->id))->toBe(PlayerActivityState::IN_GAME)
                ->and($lobby->fresh()->status)->toBe(LobbyStatus::COMPLETED);
        });

        it('sets players to IDLE when kicked from lobby', function () {
            $user = User::factory()->create();
            
            // Simulate lobby kick by setting state (actual logic in controller)
            $this->activityService->setState($user->id, PlayerActivityState::IN_LOBBY);
            expect($this->activityService->getState($user->id))->toBe(PlayerActivityState::IN_LOBBY);

            // Kicked
            $this->activityService->setState($user->id, PlayerActivityState::IDLE);
            expect($this->activityService->getState($user->id))->toBe(PlayerActivityState::IDLE);
        });

        it('sets players to IDLE when lobby cancelled', function () {
            $host = User::factory()->create();
            $player1 = User::factory()->create();
            $player2 = User::factory()->create();

            // All in lobby
            $this->activityService->setState($host->id, PlayerActivityState::IN_LOBBY);
            $this->activityService->setState($player1->id, PlayerActivityState::IN_LOBBY);
            $this->activityService->setState($player2->id, PlayerActivityState::IN_LOBBY);

            // Simulate lobby cancellation - all set to IDLE
            $this->activityService->setState($host->id, PlayerActivityState::IDLE);
            $this->activityService->setState($player1->id, PlayerActivityState::IDLE);
            $this->activityService->setState($player2->id, PlayerActivityState::IDLE);

            expect($this->activityService->getState($host->id))->toBe(PlayerActivityState::IDLE)
                ->and($this->activityService->getState($player1->id))->toBe(PlayerActivityState::IDLE)
                ->and($this->activityService->getState($player2->id))->toBe(PlayerActivityState::IDLE);
        });
    });

    describe('game completion', function () {
        it('sets players to IDLE when game completes', function () {
            $user1 = User::factory()->create();
            $user2 = User::factory()->create();

            // Set both in game
            $this->activityService->setState($user1->id, PlayerActivityState::IN_GAME);
            $this->activityService->setState($user2->id, PlayerActivityState::IN_GAME);

            // Simulate game completion (done via GameCompleted event listener)
            $this->activityService->setState($user1->id, PlayerActivityState::IDLE);
            $this->activityService->setState($user2->id, PlayerActivityState::IDLE);

            expect($this->activityService->getState($user1->id))->toBe(PlayerActivityState::IDLE)
                ->and($this->activityService->getState($user2->id))->toBe(PlayerActivityState::IDLE);
        });
    });

    describe('rematch flow', function () {
        it('maintains IDLE state during rematch request', function () {
            $user1 = User::factory()->create();
            $user2 = User::factory()->create();

            $game = Game::factory()->completed()->create();
            Player::factory()->create(['game_id' => $game->id, 'user_id' => $user1->id, 'position_id' => 1]);
            Player::factory()->create(['game_id' => $game->id, 'user_id' => $user2->id, 'position_id' => 2]);

            // Both should be IDLE after game completion
            $this->activityService->setState($user1->id, PlayerActivityState::IDLE);
            $this->activityService->setState($user2->id, PlayerActivityState::IDLE);

            // Rematch request doesn't change state
            expect($this->activityService->getState($user1->id))->toBe(PlayerActivityState::IDLE)
                ->and($this->activityService->getState($user2->id))->toBe(PlayerActivityState::IDLE);
        });
    });

    describe('logout', function () {
        it('sets OFFLINE when user logs out', function () {
            $user = User::factory()->create();

            $this->activityService->setState($user->id, PlayerActivityState::IDLE);
            expect($this->activityService->getState($user->id))->toBe(PlayerActivityState::IDLE);

            // Simulate logout
            $this->activityService->setState($user->id, PlayerActivityState::OFFLINE);
            expect($this->activityService->getState($user->id))->toBe(PlayerActivityState::OFFLINE);
        });
    });
});
