<?php

use App\Enums\GamePhase;
use App\Enums\GameStatus;
use App\GameEngine\Actions\PassCards;
use App\GameEngine\Lifecycle\Progression\CoordinatedActionProcessor;
use App\GameTitles\Hearts\HeartsTable;
use App\GameTitles\Hearts\Modes\StandardMode;
use App\Models\Games\Action;
use App\Models\Games\Game;
use App\Models\Games\Player;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

describe('Coordinated Actions Integration', function () {
    describe('All Players Submit Actions', function () {
        it('advances game phase when all 4 players submit coordinated actions', function () {
            // Create game with 4 players
            $game = Game::factory()->create([
                'title_slug' => 'hearts',
                'status' => GameStatus::ACTIVE
            ]);
            $players = collect([]);
            for ($i = 1; $i <= 4; $i++) {
                $players->push(Player::factory()->for($game)->position($i)->create());
            }

            $processor = new CoordinatedActionProcessor;
            $mode = new StandardMode($game);
            
            // Initialize game state (using Hearts internal logic)
            $gameState = HeartsTable::createNew(...$players->pluck('ulid')->all());

            // Submit actions for first 3 players
            foreach ($players->take(3) as $player) {
                $action = new PassCards(['2H', '3H', '4H']);
                
                $result = $processor->process($game, $action, $mode, $gameState, $player);
                
                // Assert not complete
                expect($result->coordinationComplete)->toBeFalse();
                
                // Manually record the action (simulating GameEngine side effect)
                Action::create([
                    'game_id' => $game->id,
                    'player_id' => $player->id,
                    'action_type' => 'pass_cards',
                    'action_details' => $action->toArray(),
                    'turn_number' => $game->turn_number ?? 1,
                    'status' => 'success',
                    'coordination_group' => $result->coordinationGroup,
                    'is_coordinated' => true,
                    'ulid' => (string) Str::ulid(),
                ]);
            }

            // Submit final player's action
            $lastPlayer = $players->last();
            $action = new PassCards(['2D', '3D', '4D']);
            
            $result = $processor->process($game, $action, $mode, $gameState, $lastPlayer);

            // Assert complete
            expect($result->coordinationComplete)->toBeTrue()
                ->and($result->updatedGameState)->toBeInstanceOf(HeartsTable::class);
                
            $finalState = $result->updatedGameState;
            expect($finalState->phase)->toBe(GamePhase::ACTIVE);
        });

        it('maintains consistent game state when players submit in different orders', function () {
            $game = Game::factory()->create(['title_slug' => 'hearts', 'status' => GameStatus::ACTIVE]);
            $players = collect([]);
            for ($i = 1; $i <= 4; $i++) {
                $players->push(Player::factory()->for($game)->position($i)->create());
            }

            $processor = new CoordinatedActionProcessor;
            $mode = new StandardMode($game);
            $gameState = HeartsTable::createNew(...$players->pluck('ulid')->all());

            // Submit in random order: 3, 1, 4, 2
            $submitOrder = [$players[2], $players[0], $players[3], $players[1]];

            foreach ($submitOrder as $index => $player) {
                $action = new PassCards(['2H', '3H', '4H']);
                $result = $processor->process($game, $action, $mode, $gameState, $player);

                if ($index < 3) {
                    expect($result->coordinationComplete)->toBeFalse();
                    Action::create([
                        'game_id' => $game->id,
                        'player_id' => $player->id,
                        'action_type' => 'pass_cards',
                        'action_details' => $action->toArray(),
                        'turn_number' => $game->turn_number ?? 1,
                        'status' => 'success',
                        'coordination_group' => $result->coordinationGroup,
                        'is_coordinated' => true,
                        'ulid' => (string) Str::ulid(),
                    ]);
                } else {
                    expect($result->coordinationComplete)->toBeTrue();
                }
            }
        });
    });
});
