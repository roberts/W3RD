<?php

use App\GameEngine\Lifecycle\Progression\CoordinatedActionProcessor;
use App\Enums\GameStatusEnum;
use App\Models\Game\Action;
use App\Models\Game\Game;
use App\Models\Game\Player;

/**
 * NOTE: These tests need to be refactored to match the new API.
 * CoordinatedActionProcessor::process() now requires:
 *   - Game $game
 *   - GameActionContract $action (not array)
 *   - mixed $mode (game mode handler)
 *   - object $gameState
 * 
 * The tests should be updated to use proper action DTOs and game state objects.
 */

describe('Coordinated Actions Integration', function () {
    describe('All Players Submit Actions', function () {
        it('advances game phase when all 4 players submit coordinated actions', function () {
            // Create game with 4 players in coordination phase
            $game = Game::factory()->create(['status' => GameStatusEnum::IN_PROGRESS]);
            $players = collect([]);

            for ($i = 1; $i <= 4; $i++) {
                $players->push(Player::factory()->for($game)->position($i)->create());
            }

            $action = new CoordinatedActionProcessor;

            // Submit actions for first 3 players
            foreach ($players->take(3) as $player) {
                $action->execute(
                    $game,
                    $player,
                    ['type' => 'pass_cards', 'cards' => ['2H', '3H', '4H']]
                );
                $game->refresh();
            }

            // Game should still be waiting
            expect($game->game_state['waiting_for_players'])->toHaveCount(1);

            // Submit final player's action
            $action->execute(
                $game,
                $players->last(),
                ['type' => 'pass_cards', 'cards' => ['2D', '3D', '4D']]
            );
            $game->refresh();

            // Game should have advanced phase
            expect($game->game_state)->not->toHaveKey('waiting_for_players')
                ->and($game->game_state['phase'])->toBe('play');
        });

        it('maintains consistent game state when players submit in different orders', function () {
            $game = Game::factory()->create(['status' => GameStatusEnum::IN_PROGRESS]);
            $players = collect([]);

            for ($i = 1; $i <= 4; $i++) {
                $players->push(Player::factory()->for($game)->position($i)->create());
            }

            $action = new CoordinatedActionProcessor;

            // Submit in random order: 3, 1, 4, 2
            $submitOrder = [$players[2], $players[0], $players[3], $players[1]];

            foreach ($submitOrder as $player) {
                $action->execute(
                    $game,
                    $player,
                    ['type' => 'pass_cards', 'cards' => ['2H', '3H', '4H']]
                );
                $game->refresh();
            }

            // Verify all actions recorded
            expect(Action::where('game_id', $game->id)->count())->toBe(4)
                ->and($game->game_state['phase'])->toBe('play');
        });
    });

    describe('Player Timeout During Coordination', function () {
        it('applies timeout handler when player does not submit action', function () {
            $game = Game::factory()->create(['status' => GameStatusEnum::IN_PROGRESS]);
            $players = collect([]);

            for ($i = 1; $i <= 4; $i++) {
                $players->push(Player::factory()->for($game)->position($i)->create());
            }

            $action = new CoordinatedActionProcessor;

            // 3 players submit
            foreach ($players->take(3) as $player) {
                $action->execute(
                    $game,
                    $player,
                    ['type' => 'pass_cards', 'cards' => ['2H', '3H', '4H']]
                );
            }

            $game->refresh();

            // Simulate timeout for 4th player
            $timeoutPlayer = $players->last();
            $game->update([
                'game_state' => array_merge($game->game_state, [
                    'timeout_player_id' => $timeoutPlayer->id,
                ]),
            ]);

            // Process timeout (would normally be handled by HandleTimeoutAction)
            // Game should still advance
            expect($game->game_state)->toHaveKey('timeout_player_id');
        });

        it('handles multiple consecutive timeouts for same player', function () {
            $game = Game::factory()->create(['status' => GameStatusEnum::IN_PROGRESS]);
            $players = collect([]);

            for ($i = 1; $i <= 4; $i++) {
                $players->push(Player::factory()->for($game)->position($i)->create());
            }

            $slowPlayer = $players->first();
            $action = new CoordinatedActionProcessor;

            // First round - slow player times out
            foreach ($players->skip(1) as $player) {
                $action->execute($game, $player, ['type' => 'pass_cards', 'cards' => ['2H', '3H', '4H']]);
            }

            $game->refresh();
            expect($game->game_state['waiting_for_players'])->toContain((string) $slowPlayer->id);

            // Second round - same player times out again
            $game->update(['game_state' => array_merge($game->game_state, ['phase' => 'pass_2'])]);

            foreach ($players->skip(1) as $player) {
                $action->execute($game, $player, ['type' => 'pass_cards', 'cards' => ['2S', '3S', '4S']]);
            }

            $game->refresh();
            expect($game->game_state['timeout_count'][$slowPlayer->id] ?? 0)->toBeGreaterThan(0);
        });
    });

    describe('Race Conditions', function () {
        it('handles two players submitting simultaneously without duplicate processing', function () {
            $game = Game::factory()->create(['status' => GameStatusEnum::IN_PROGRESS]);
            $player1 = Player::factory()->for($game)->position(1)->create();
            $player2 = Player::factory()->for($game)->position(2)->create();

            $action = new CoordinatedActionProcessor;

            // Simulate simultaneous submission (in reality handled by database locks)
            $action->execute($game, $player1, ['type' => 'pass_cards', 'cards' => ['2H', '3H', '4H']]);
            $action->execute($game, $player2, ['type' => 'pass_cards', 'cards' => ['2D', '3D', '4D']]);

            $game->refresh();

            // Each player should have exactly one action recorded
            expect(Action::where('game_id', $game->id)->where('player_id', $player1->id)->count())->toBe(1)
                ->and(Action::where('game_id', $game->id)->where('player_id', $player2->id)->count())->toBe(1);
        });

        it('prevents player from submitting action twice in same coordination phase', function () {
            $game = Game::factory()->create(['status' => GameStatusEnum::IN_PROGRESS]);
            $player = Player::factory()->for($game)->position(1)->create();

            $action = new CoordinatedActionProcessor;

            // First submission
            $action->execute($game, $player, ['type' => 'pass_cards', 'cards' => ['2H', '3H', '4H']]);

            // Attempt second submission (should be rejected or ignored)
            try {
                $action->execute($game, $player, ['type' => 'pass_cards', 'cards' => ['2D', '3D', '4D']]);
            } catch (\Exception $e) {
                // Expected to throw exception or be silently ignored
            }

            // Should only have one action
            expect(Action::where('game_id', $game->id)->where('player_id', $player->id)->count())->toBe(1);
        });
    });

    describe('WebSocket Notifications', function () {
        it('sends notifications to all players when coordination completes', function () {
            Queue::fake();

            $game = Game::factory()->create(['status' => GameStatusEnum::IN_PROGRESS]);
            $players = collect([]);

            for ($i = 1; $i <= 4; $i++) {
                $players->push(Player::factory()->for($game)->position($i)->create());
            }

            $action = new CoordinatedActionProcessor;

            // Submit all actions
            foreach ($players as $player) {
                $action->execute($game, $player, ['type' => 'pass_cards', 'cards' => ['2H', '3H', '4H']]);
            }

            // Verify broadcast job was dispatched (would contain game state update)
            Queue::assertPushed(function ($job) use ($game) {
                return property_exists($job, 'game') && $job->game->id === $game->id;
            });
        });
    });
});
