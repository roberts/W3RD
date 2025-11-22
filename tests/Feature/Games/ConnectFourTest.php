<?php

declare(strict_types=1);

use App\Exceptions\InvalidGameConfigurationException;
use App\GameTitles\ConnectFour\ConnectFourBoard;
use App\GameTitles\ConnectFour\Modes\StandardMode;
use App\Models\Game\Game;
use Illuminate\Support\Str;

describe('ConnectFour Game Logic', function () {
    test('can create initial game state', function () {
        $game = new Game(['game_state' => []]);
        $mode = new StandardMode($game);
        $playerOne = (string) Str::ulid();
        $playerTwo = (string) Str::ulid();

        $state = $mode->createInitialState($playerOne, $playerTwo);

        expect($state)->toBeInstanceOf(ConnectFourBoard::class)
            ->and($state->currentPlayerUlid)->toBe($playerOne)
            ->and($state->players)->toHaveCount(2)
            ->and($state->players[$playerOne]->color)->toBe('red')
            ->and($state->players[$playerTwo]->color)->toBe('yellow');
    });

    test('requires exactly 2 players', function () {
        $game = new Game(['game_state' => []]);
        $mode = new StandardMode($game);
        $playerOne = (string) Str::ulid();

        expect(fn () => $mode->createInitialState($playerOne))
            ->toThrow(InvalidGameConfigurationException::class, 'Connect Four requires exactly 2 players');
    });

    test('board is properly initialized', function () {
        $game = new Game(['game_state' => []]);
        $mode = new StandardMode($game);
        $playerOne = (string) Str::ulid();
        $playerTwo = (string) Str::ulid();

        $state = $mode->createInitialState($playerOne, $playerTwo);

        // Board should be empty
        for ($row = 0; $row < 6; $row++) {
            for ($col = 0; $col < 7; $col++) {
                expect($state->getPieceAt($row, $col))->toBeNull();
            }
        }
    });
});
