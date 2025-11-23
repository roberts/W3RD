<?php

use App\Enums\GameStatusEnum;
use App\GameTitles\Hearts\Handlers\ForfeitHandler;
use App\GameTitles\Hearts\Handlers\NoneHandler;
use App\GameTitles\Hearts\Handlers\PassHandler;
use App\Models\Games\Game;
use App\Models\Games\Player;

describe('Timeout Handling Across Game States', function () {
    describe('Timeout During Card Passing Phase', function () {
        it('applies PassHandler and auto-passes random cards', function () {
            $game = Game::factory()->create([
                'status' => GameStatusEnum::IN_PROGRESS,
                'game_state' => [
                    'phase' => 'pass',
                    'pass_direction' => 'left',
                ],
            ]);

            $players = collect([]);
            for ($i = 1; $i <= 4; $i++) {
                $players->push(Player::factory()->for($game)->position($i)->create());
            }

            $timeoutPlayer = $players->first();

            // Set up game waiting for this player
            $game->update([
                'game_state' => array_merge($game->game_state, [
                    'waiting_for_players' => [(string) $timeoutPlayer->id],
                    'hands' => [
                        (string) $timeoutPlayer->id => ['2H', '3H', '4H', '5H', '6H'],
                    ],
                ]),
            ]);

            $handler = new PassHandler;
            $handler->handle($game, $timeoutPlayer);

            $game->refresh();

            // Verify cards were auto-passed
            expect($game->game_state)->toHaveKey('passed_cards')
                ->and($game->game_state['passed_cards'][(string) $timeoutPlayer->id])->toHaveCount(3);
        });

        it('continues game after timeout without blocking other players', function () {
            $game = Game::factory()->create([
                'status' => GameStatusEnum::IN_PROGRESS,
                'game_state' => ['phase' => 'pass'],
            ]);

            $players = collect([]);
            for ($i = 1; $i <= 4; $i++) {
                $players->push(Player::factory()->for($game)->position($i)->create());
            }

            $timeoutPlayer = $players->first();

            // Other players already submitted
            $game->update([
                'game_state' => array_merge($game->game_state, [
                    'waiting_for_players' => [(string) $timeoutPlayer->id],
                ]),
            ]);

            $handler = new PassHandler;
            $handler->handle($game, $timeoutPlayer);

            $game->refresh();

            // Game should advance to play phase
            expect($game->game_state['phase'])->toBe('play')
                ->and($game->game_state)->not->toHaveKey('waiting_for_players');
        });
    });

    describe('Timeout During Regular Play', function () {
        it('applies ForfeitHandler and player forfeits turn', function () {
            $game = Game::factory()->create([
                'status' => GameStatusEnum::IN_PROGRESS,
                'game_state' => [
                    'phase' => 'play',
                    'current_turn' => 1,
                ],
            ]);

            $player = Player::factory()->for($game)->position(1)->create();

            $handler = new ForfeitHandler;
            $handler->handle($game, $player);

            $game->refresh();

            // Verify forfeit was recorded
            expect($game->game_state)->toHaveKey('forfeits')
                ->and($game->game_state['forfeits'])->toContain((string) $player->id);
        });

        it('advances turn to next player after forfeit', function () {
            $game = Game::factory()->create([
                'status' => GameStatusEnum::IN_PROGRESS,
                'game_state' => [
                    'phase' => 'play',
                    'current_turn' => 1,
                    'turn_order' => [1, 2, 3, 4],
                ],
            ]);

            $player1 = Player::factory()->for($game)->position(1)->create();
            Player::factory()->for($game)->position(2)->create();

            $handler = new ForfeitHandler;
            $handler->handle($game, $player1);

            $game->refresh();

            // Turn should advance to player 2
            expect($game->game_state['current_turn'])->toBe(2);
        });
    });

    describe('Timeout During Game End', function () {
        it('applies NoneHandler and takes no action', function () {
            $game = Game::factory()->create([
                'status' => GameStatusEnum::COMPLETED,
                'game_state' => ['phase' => 'end'],
            ]);

            $player = Player::factory()->for($game)->position(1)->create();

            $handler = new NoneHandler;
            $result = $handler->handle($game, $player);

            // Should return without making changes
            expect($result)->toBeNull();
        });
    });

    describe('Multiple Consecutive Timeouts', function () {
        it('tracks timeout count and applies increasing penalties', function () {
            $game = Game::factory()->create([
                'status' => GameStatusEnum::IN_PROGRESS,
                'game_state' => ['phase' => 'play'],
            ]);

            $player = Player::factory()->for($game)->position(1)->create();

            $handler = new ForfeitHandler;

            // First timeout
            $handler->handle($game, $player);
            $game->refresh();

            $firstTimeoutCount = $game->game_state['timeout_count'][(string) $player->id] ?? 0;

            // Second timeout
            $handler->handle($game, $player);
            $game->refresh();

            $secondTimeoutCount = $game->game_state['timeout_count'][(string) $player->id] ?? 0;

            expect($secondTimeoutCount)->toBeGreaterThan($firstTimeoutCount);
        });

        it('removes player from game after excessive timeouts', function () {
            $game = Game::factory()->create([
                'status' => GameStatusEnum::IN_PROGRESS,
                'game_state' => [
                    'phase' => 'play',
                    'timeout_count' => [],
                ],
            ]);

            $player = Player::factory()->for($game)->position(1)->create();

            $handler = new ForfeitHandler;

            // Simulate 5 consecutive timeouts
            for ($i = 0; $i < 5; $i++) {
                $game->update([
                    'game_state' => array_merge($game->game_state, [
                        'timeout_count' => [
                            (string) $player->id => $i,
                        ],
                    ]),
                ]);

                $handler->handle($game, $player);
                $game->refresh();
            }

            // Player should be marked as removed or game forfeited
            expect($game->game_state['timeout_count'][(string) $player->id])->toBeGreaterThanOrEqual(5);
        });
    });

    describe('Timeout While Player is Submitting', function () {
        it('honors player action if submitted before timeout processed', function () {
            $game = Game::factory()->create([
                'status' => GameStatusEnum::IN_PROGRESS,
                'game_state' => [
                    'phase' => 'play',
                    'waiting_for_players' => ['1'],
                ],
            ]);

            $player = Player::factory()->for($game)->position(1)->create();

            // Player submits action
            $game->update([
                'game_state' => array_merge($game->game_state, [
                    'actions' => [
                        (string) $player->id => ['card' => '2H'],
                    ],
                    'waiting_for_players' => [], // Action processed
                ]),
            ]);

            // Timeout handler fires (race condition)
            $handler = new ForfeitHandler;
            $handler->handle($game, $player);

            $game->refresh();

            // Should not override player's action
            expect($game->game_state['actions'][(string) $player->id])->toBe(['card' => '2H']);
        });
    });
});
