<?php

declare(strict_types=1);

use App\Games\ValidateFour\Actions\PopOut;
use App\Games\ValidateFour\Modes\FiveMode;
use App\Games\ValidateFour\Modes\PopOutMode;
use App\Models\Game\Game;
use Illuminate\Support\Str;

describe('ValidateFour Modes', function () {
    test('PopOutMode has PopOut action', function () {
        $game = new Game(['game_state' => []]);
        $mode = new PopOutMode($game);
        
        $reflection = new ReflectionClass($mode);
        $method = $reflection->getMethod('getGameConfig');
        $method->setAccessible(true);
        $config = $method->invoke($mode);

        $actions = $config->getActionRegistry();

        expect($actions)->toHaveKey(PopOut::class);
    });

    test('FiveMode has correct board size', function () {
        $game = new Game(['game_state' => []]);
        $mode = new FiveMode($game);
        $playerOne = (string) Str::ulid();
        $playerTwo = (string) Str::ulid();

        $state = $mode->createInitialState($playerOne, $playerTwo);

        // FiveMode should be 9x7
        expect($state->board)->toHaveCount(7) // rows
            ->and($state->board[0])->toHaveCount(9); // columns
    });
});
