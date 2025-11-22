<?php

use App\GameEngine\Actions\PlayCard;
use App\GameTitles\Hearts\HeartsTable;
use App\GameTitles\Hearts\Modes\StandardMode;
use App\Models\Game\Game;

test('hearts engine validates and applies play card', function () {
    $game = new Game(['game_state' => []]);
    $mode = new StandardMode($game);
    $state = $mode->createInitialState('p1', 'p2', 'p3', 'p4');

    // Setup hand for p1
    $hands = $state->hands;
    $hands['p1'] = ['C2', 'H2', 'SQ'];

    $state = new HeartsTable(
        players: $state->players,
        currentPlayerUlid: 'p1',
        winnerUlid: null,
        phase: $state->phase,
        status: $state->status,
        roundNumber: 1,
        hands: $hands,
        currentTrick: [],
        trickLeaderUlid: 'p1',
        heartsBroken: false,
        isDraw: false,
    );

    $action = new PlayCard('C2');

    $validation = $mode->validateAction($state, $action);
    expect($validation->isValid)->toBeTrue();

    $newState = $mode->applyAction($state, $action);
    expect($newState->currentTrick['p1'])->toBe('C2');
    expect($newState->hands['p1'])->not->toContain('C2');
});
