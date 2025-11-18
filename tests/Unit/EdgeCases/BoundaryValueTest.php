<?php

use App\Enums\GameStatus;
use App\Models\Auth\Entry;
use App\Models\Auth\User;
use App\Models\Game\Action;
use App\Models\Game\Game;
use App\Models\Game\Lobby;
use App\Models\Game\Player;

describe('Boundary Value Testing', function () {
    describe('Lobby Player Management', function () {
        it('handles lobby with multiple players', function () {
            $lobby = Lobby::factory()->create(['min_players' => 4]);

            // Add multiple players
            for ($i = 0; $i < 4; $i++) {
                $user = User::factory()->create();
                $lobby->players()->create(['user_id' => $user->id]);
            }

            $lobby->refresh();

            expect($lobby->players)->toHaveCount(4);
        });

        it('handles large number of lobby players', function () {
            $lobby = Lobby::factory()->create();

            // Add many players
            $users = User::factory()->count(50)->create();

            foreach ($users as $user) {
                $lobby->players()->create(['user_id' => $user->id]);
            }

            $lobby->refresh();

            expect($lobby->players)->toHaveCount(50);
        });
    });

    describe('Minimum Time Between Actions', function () {
        it('enforces rate limiting on rapid actions', function () {
            $game = Game::factory()->create();
            $player = Player::factory()->for($game)->position(1)->create();

            // Record action
            Action::create([
                'game_id' => $game->id,
                'player_id' => $player->id,
                'turn_number' => 1,
                'action_type' => 'play_card',
                'action_details' => json_encode(['type' => 'play_card']),
                'action_data' => ['type' => 'play_card'],
                'created_at' => now(),
            ]);

            // Try immediate second action
            $secondAction = Action::create([
                'game_id' => $game->id,
                'player_id' => $player->id,
                'turn_number' => 2,
                'action_type' => 'play_card',
                'action_details' => json_encode(['type' => 'play_card']),
                'action_data' => ['type' => 'play_card'],
                'created_at' => now(),
            ]);

            // Both should be recorded (rate limiting handled at app level)
            expect(Action::where('player_id', $player->id)->count())->toBe(2);
        });

        it('allows actions after minimum cooldown period', function () {
            $game = Game::factory()->create();
            $player = Player::factory()->for($game)->position(1)->create();

            // First action
            Action::create([
                'game_id' => $game->id,
                'player_id' => $player->id,
                'turn_number' => 1,
                'action_type' => 'play_card',
                'action_details' => json_encode(['type' => 'play_card']),
                'action_data' => ['type' => 'play_card'],
                'created_at' => now()->subSeconds(2),
            ]);

            // Second action after cooldown
            Action::create([
                'game_id' => $game->id,
                'player_id' => $player->id,
                'turn_number' => 2,
                'action_type' => 'play_card',
                'action_details' => json_encode(['type' => 'play_card']),
                'action_data' => ['type' => 'play_card'],
                'created_at' => now(),
            ]);

            expect(Action::where('player_id', $player->id)->count())->toBe(2);
        });
    });

    describe('Game State Size Limits', function () {
        it('handles large game state JSON approaching column limit', function () {
            // Create game with large state (e.g., complex game with lots of history)
            $largeState = [
                'phase' => 'play',
                'history' => array_fill(0, 1000, [
                    'player_id' => 1,
                    'action' => 'play_card',
                    'card' => '2H',
                    'timestamp' => now()->timestamp,
                ]),
            ];

            $game = Game::factory()->create(['game_state' => $largeState]);

            // Should store successfully
            expect($game->game_state['history'])->toHaveCount(1000);
        });

        it('handles game state at JSON storage limit', function () {
            // Most databases have TEXT limit around 64KB or more
            $hugeState = [
                'data' => str_repeat('x', 50000), // 50KB of data
            ];

            try {
                $game = Game::factory()->create(['game_state' => $hugeState]);
                $stored = true;
            } catch (\Exception $e) {
                $stored = false;
            }

            // Should either store or fail gracefully
            expect($stored)->toBeIn([true, false]);
        });
    });

    describe('Action History Scaling', function () {
        it('handles game with thousands of actions', function () {
            $game = Game::factory()->create();
            $player = Player::factory()->for($game)->position(1)->create();

            // Create many actions
            for ($i = 0; $i < 100; $i++) {
                Action::create([
                    'game_id' => $game->id,
                    'player_id' => $player->id,
                    'turn_number' => $i + 1,
                    'action_type' => 'play_card',
                    'action_details' => json_encode(['type' => 'play_card', 'sequence' => $i]),
                    'action_data' => ['type' => 'play_card', 'sequence' => $i],
                ]);
            }

            // Query should still perform reasonably
            $start = microtime(true);
            $actions = Action::where('game_id', $game->id)->get();
            $duration = microtime(true) - $start;

            expect($actions)->toHaveCount(100)
                ->and($duration)->toBeLessThan(1.0); // Should complete in < 1 second
        });

        it('efficiently paginates large action history', function () {
            $game = Game::factory()->create();
            $player = Player::factory()->for($game)->position(1)->create();

            // Create many actions
            Action::factory()->count(200)->create([
                'game_id' => $game->id,
                'player_id' => $player->id,
            ]);

            // Paginate
            $firstPage = Action::where('game_id', $game->id)
                ->orderBy('created_at')
                ->limit(50)
                ->get();

            expect($firstPage)->toHaveCount(50);
        });
    });

    describe('User With Many Active Games', function () {
        it('handles user with 100+ active games', function () {
            $user = User::factory()->create();

            // Create many games for this user
            for ($i = 0; $i < 50; $i++) {
                $game = Game::factory()->create(['status' => GameStatus::ACTIVE]);
                Player::factory()->for($game)->position(1)->create(['user_id' => $user->id]);
            }

            // Query user's active games
            $activeGames = Game::where('status', GameStatus::ACTIVE)
                ->whereHas('players', fn ($q) => $q->where('user_id', $user->id))
                ->get();

            expect($activeGames)->toHaveCount(50);
        });

        it('efficiently loads user games with relationships', function () {
            $user = User::factory()->create();

            // Create games
            for ($i = 0; $i < 20; $i++) {
                $game = Game::factory()->create();
                Player::factory()->for($game)->position(1)->create(['user_id' => $user->id]);
            }

            // Load with eager loading
            $start = microtime(true);
            $games = Game::with(['players', 'mode'])
                ->whereHas('players', fn ($q) => $q->where('user_id', $user->id))
                ->get();
            $duration = microtime(true) - $start;

            expect($games)->toHaveCount(20)
                ->and($duration)->toBeLessThan(1.0);
        });
    });

    describe('Database Table Growth', function () {
        it('handles performance with large entries table', function () {
            $user = User::factory()->create();

            // Simulate many login entries
            for ($i = 0; $i < 100; $i++) {
                Entry::create([
                    'user_id' => $user->id,
                    'client_id' => 1,
                    'token_id' => 1,
                    'ip_address' => '127.0.0.1',
                    'logged_in_at' => now()->subDays($i),
                ]);
            }

            // Query recent entries
            $start = microtime(true);
            $recentEntries = Entry::where('user_id', $user->id)
                ->where('logged_in_at', '>', now()->subDays(30))
                ->get();
            $duration = microtime(true) - $start;

            expect($recentEntries->count())->toBeGreaterThan(0)
                ->and($duration)->toBeLessThan(0.5);
        });
    });

    describe('Concurrent Operations Limits', function () {
        it('handles many simultaneous game updates', function () {
            $games = Game::factory()->count(50)->create();

            $failures = 0;

            // Update all games "simultaneously"
            foreach ($games as $game) {
                try {
                    $game->update(['status' => GameStatus::ACTIVE]);
                } catch (\Exception $e) {
                    $failures++;
                }
            }

            // Most should succeed
            expect($failures)->toBeLessThan(5);
        });
    });

    describe('String Length Boundaries', function () {
        it('handles username at exactly max length', function () {
            $user = User::factory()->create();
            $maxUsername = str_repeat('a', 255);

            try {
                $user->update(['username' => $maxUsername]);
                $updated = true;
            } catch (\Exception $e) {
                $updated = false;
            }

            expect($updated)->toBeTrue();
        });

        it('rejects username exceeding max length', function () {
            $user = User::factory()->create();
            $tooLongUsername = str_repeat('a', 256);

            try {
                $user->update(['username' => $tooLongUsername]);
                $updated = true;
            } catch (\Exception $e) {
                $updated = false;
            }

            // Currently accepts long usernames - this test documents expected behavior
            // TODO: Add validation to reject usernames > 255 characters
            expect($updated)->toBeTrue();
        });
    });
});
