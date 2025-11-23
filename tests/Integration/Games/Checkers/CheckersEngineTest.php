<?php

use App\GameEngine\Actions\JumpPiece;
use App\GameEngine\Actions\MovePiece;
use App\GameTitles\Checkers\CheckersBoard;
use App\GameTitles\Checkers\Modes\StandardMode;
use App\Models\Games\Game;

test('checkers engine validates and applies move piece', function () {
    $game = new Game(['game_state' => []]);
    $mode = new StandardMode($game);
    $state = $mode->createInitialState('p1', 'p2');

    // Red (p1) moves from 5,0 to 4,1
    $action = new MovePiece(5, 0, 4, 1);

    $validation = $mode->validateAction($state, $action);
    expect($validation->isValid)->toBeTrue();

    $newState = $mode->applyAction($state, $action);
    expect($newState->getPieceAt(5, 0))->toBeNull();
    expect($newState->getPieceAt(4, 1))->not->toBeNull();
});

test('checkers engine rejects invalid move', function () {
    $game = new Game(['game_state' => []]);
    $mode = new StandardMode($game);
    $state = $mode->createInitialState('p1', 'p2');

    // Red (p1) tries to move backwards from 5,0 to 6,1 (occupied)
    $action = new MovePiece(5, 0, 6, 1);

    $validation = $mode->validateAction($state, $action);
    expect($validation->isValid)->toBeFalse();
});

test('checkers engine validates and applies jump piece', function () {
    $game = new Game(['game_state' => []]);
    $mode = new StandardMode($game);
    $state = $mode->createInitialState('p1', 'p2');

    // Setup a jump scenario
    // Place red at 4,1 and black at 3,2
    $board = $state->board;
    $board[4][1] = ['player' => 'p1', 'king' => false];
    $board[3][2] = ['player' => 'p2', 'king' => false];
    $board[5][0] = null; // Remove original red
    $board[2][3] = null; // Remove original black

    $state = new CheckersBoard(
        players: $state->players,
        currentPlayerUlid: 'p1',
        winnerUlid: null,
        phase: $state->phase,
        status: $state->status,
        board: $board,
        isDraw: false,
    );

    // Red jumps from 4,1 to 2,3 capturing 3,2
    $action = new JumpPiece(4, 1, 2, 3, 3, 2);

    $validation = $mode->validateAction($state, $action);
    expect($validation->isValid)->toBeTrue();

    $newState = $mode->applyAction($state, $action);
    expect($newState->getPieceAt(4, 1))->toBeNull();
    expect($newState->getPieceAt(2, 3))->not->toBeNull();
    expect($newState->getPieceAt(3, 2))->toBeNull(); // Captured
});
