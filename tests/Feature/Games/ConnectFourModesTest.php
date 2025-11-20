<?php

declare(strict_types=1);

use App\Games\ConnectFour\Actions\PopOut;
use App\Games\ConnectFour\Modes\FiveMode;
use App\Games\ConnectFour\Modes\PopOutMode;
use App\Models\Game\Game;
use Illuminate\Support\Str;

describe('ConnectFour Modes', function () {
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
