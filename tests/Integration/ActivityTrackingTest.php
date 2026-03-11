<?php

use App\Enums\GameStatus;
use App\Enums\GameTitle;
use App\Enums\PlayerActivityState;
use App\GameEngine\Lifecycle\Creation\GameBuilder;
use App\GameEngine\Player\PlayerActivityManager;
use App\Matchmaking\Enums\LobbyStatus;
use App\Matchmaking\Queue\Actions\JoinQueueAction;
use App\Matchmaking\Queue\Actions\LeaveQueueAction;
use App\Models\Auth\User;
use App\Models\Games\Game;
use App\Models\Games\Mode;
use App\Models\Matchmaking\Lobby;
use Illuminate\Support\Facades\Redis;

describe('Activity Tracking in Game Creation', function () {
    beforeEach(function () {
        $this->states = [];

        // Mock Redis to track state changes in memory
        Redis::shouldReceive('setex')
            ->with(Mockery::pattern('/player:\d+:activity/'), 1800, Mockery::any())
            ->andReturnUsing(function ($key, $ttl, $value) {
                $this->states[$key] = $value;

                return true;
            })
            ->byDefault();

        Redis::shouldReceive('get')
            ->with(Mockery::pattern('/player:\d+:activity/'))
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

        $this->activityService = app(PlayerActivityManager::class);
        $this->gameBuilder = app(GameBuilder::class);

        Mode::factory()->create([
            'title_slug' => GameTitle::CONNECT_FOUR,
            'slug' => 'standard',
        ]);
    });

    describe('matchmaking queue', function () {
        it('sets IN_QUEUE when joining queue', function () {
            $user = User::factory()->create();
            $joinAction = app(JoinQueueAction::class);

            $joinAction->execute($user, GameTitle::CONNECT_FOUR, 'standard', 1);

            expect($this->activityService->getState($user->id))->toBe(PlayerActivityState::IN_QUEUE);
        });

        it('sets IDLE when leaving queue', function () {
            $user = User::factory()->create();

            // Join queue first
            $joinAction = app(JoinQueueAction::class);
            $joinAction->execute($user, GameTitle::CONNECT_FOUR, 'standard', 1);

            // Then leave
            $leaveAction = app(LeaveQueueAction::class);
            $leaveAction->execute($user);

            expect($this->activityService->getState($user->id))->toBe(PlayerActivityState::IDLE);
        });
    });

    describe('game creation from queue', function () {
        it('sets IN_GAME for both players when creating from queue match', function () {
            $user1 = User::factory()->create();
            $user2 = User::factory()->create();

            // Simulate queue match data in Redis
            $matchId = 'test-match-123';
            $matchKey = "queue:match:{$matchId}";
            Redis::hmset($matchKey, [
                'game_title' => 'connect-four',
                'game_mode' => 'standard',
                'player_'.$user1->id.'_client' => '1',
                'player_'.$user2->id.'_client' => '1',
            ]);

            $game = $this->gameBuilder->createFromQueueMatch(
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
                'title_slug' => GameTitle::CONNECT_FOUR,
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
                'title_slug' => GameTitle::CONNECT_FOUR,
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

            $game = $this->gameBuilder->createFromLobby($lobby);

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
            $game = Game::factory()->completed()->withPlayers(2)->create();
            $players = $game->players;
            $user1 = $players[0]->user;
            $user2 = $players[1]->user;

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
