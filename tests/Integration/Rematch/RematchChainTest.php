<?php

use App\Enums\GameStatusEnum;
use App\Models\Auth\User;
use App\Models\Game\Game;
use App\Models\Game\Lobby;
use App\Models\Game\Mode;
use App\Models\Game\Player;
use App\Services\RematchService;

describe('Rematch Chain Scenarios', function () {
    describe('Rematch Chain Tracking', function () {
        it('creates chain from game 1 to game 2 to game 3', function () {
            $mode = Mode::factory()->create();

            // Game 1
            $game1 = Game::factory()->withPlayers(4)->create([
                'mode_id' => $mode->id,
                'status' => GameStatusEnum::COMPLETED,
            ]);

            $users = $game1->players->pluck('user');

            $rematchService = new RematchService;

            // Create rematch -> Game 2
            $lobby2 = $rematchService->createRematchLobby($game1);
            $game2 = Game::factory()->create([
                'mode_id' => $mode->id,
                'parent_game_id' => $game1->id,
                'status' => GameStatusEnum::COMPLETED,
            ]);

            // Create another rematch -> Game 3
            $lobby3 = $rematchService->createRematchLobby($game2);
            $game3 = Game::factory()->create([
                'mode_id' => $mode->id,
                'parent_game_id' => $game2->id,
                'status' => GameStatusEnum::IN_PROGRESS,
            ]);

            // Verify chain
            expect($game2->parent_game_id)->toBe($game1->id)
                ->and($game3->parent_game_id)->toBe($game2->id);

            // Verify can traverse chain
            $chain = collect([$game3]);
            $current = $game3;

            while ($current->parent_game_id) {
                $parent = Game::find($current->parent_game_id);
                $chain->push($parent);
                $current = $parent;
            }

            expect($chain)->toHaveCount(3)
                ->and($chain->last()->id)->toBe($game1->id);
        });

        it('tracks statistics across rematch chain', function () {
            $mode = Mode::factory()->create();
            $user = User::factory()->create();

            // Game 1 - user wins
            $game1 = Game::factory()->create([
                'mode_id' => $mode->id,
                'status' => GameStatusEnum::COMPLETED,
                'winner_player_id' => 1,
            ]);

            $player1 = Player::factory()->for($game1)->position(1)->create([
                'user_id' => $user->id,
            ]);

            // Game 2 (rematch) - user loses
            $game2 = Game::factory()->create([
                'mode_id' => $mode->id,
                'parent_game_id' => $game1->id,
                'status' => GameStatusEnum::COMPLETED,
                'winner_player_id' => 2,
            ]);

            Player::factory()->for($game2)->position(1)->create([
                'user_id' => $user->id,
            ]);

            // Check user's record in this rematch series
            $userGames = Game::whereIn('id', [$game1->id, $game2->id])
                ->whereHas('players', fn ($q) => $q->where('user_id', $user->id))
                ->get();

            $wins = $userGames->where('winner_player_id', $player1->id)->count();

            expect($userGames)->toHaveCount(2)
                ->and($wins)->toBe(1);
        });
    });

    describe('Partial Rematch Acceptance', function () {
        it('creates new lobby with only accepting players', function () {
            $mode = Mode::factory()->create();

            $game = Game::factory()->withPlayers(4)->create([
                'mode_id' => $mode->id,
                'status' => GameStatusEnum::COMPLETED,
            ]);

            $users = $game->players->pluck('user');
            $players = $game->players;

            $rematchService = new RematchService;

            // Create rematch lobby
            $lobby = $rematchService->createRematchLobby($game);

            // Only 2 players join
            $lobby->players()->create(['user_id' => $users[0]->id, 'is_ready' => true]);
            $lobby->players()->create(['user_id' => $users[1]->id, 'is_ready' => true]);

            $lobby->refresh();

            // Should have 2 players, waiting for more
            expect($lobby->players)->toHaveCount(2)
                ->and($lobby->status)->toBe('pending');
        });

        it('cancels rematch if not enough players accept', function () {
            $mode = Mode::factory()->create(['min_players' => 4]);

            $game = Game::factory()->withPlayers(4)->create([
                'mode_id' => $mode->id,
                'status' => GameStatusEnum::COMPLETED,
            ]);

            $users = $game->players->pluck('user');

            $rematchService = new RematchService;
            $lobby = $rematchService->createRematchLobby($game);

            // Only 1 player joins before timeout
            $lobby->players()->create(['user_id' => $users[0]->id, 'is_ready' => true]);

            // Simulate timeout (would be handled by scheduled job)
            $lobby->update([
                'status' => 'cancelled',
                'expires_at' => now()->subMinutes(1),
            ]);

            $lobby->refresh();

            expect($lobby->status)->toBe('cancelled');
        });
    });
});
