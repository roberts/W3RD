<?php

namespace App\Enums;

/**
 * Game error codes that apply universally across all games.
 *
 * These codes represent common game state violations that every game must handle.
 * Individual games should extend this with their own game-specific error enums.
 */
enum GameErrorCode: string
{
    case NOT_PLAYER_TURN = 'not_player_turn';
    case GAME_ALREADY_COMPLETED = 'game_already_completed';
    case GAME_NOT_ACTIVE = 'game_not_active';
    case PLAYER_NOT_IN_GAME = 'player_not_in_game';
    case ACTION_TIMEOUT = 'action_timeout';
    case INVALID_STATE = 'invalid_state';
    case INVALID_ACTION_PARAMETERS = 'invalid_action_parameters';
    case INVALID_ACTION_TYPE = 'invalid_action_type';
    case INVALID_PHASE = 'invalid_phase';
    case WAITING_FOR_OTHER_PLAYERS = 'waiting_for_other_players';
    case RESOURCE_UNAVAILABLE = 'resource_unavailable';

    public function severity(): string
    {
        return match ($this) {
            self::WAITING_FOR_OTHER_PLAYERS => 'warning',
            self::INVALID_STATE => 'critical',
            default => 'error',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::NOT_PLAYER_TURN => 'Applies to: Turn-based games. Indicates that a player attempted to act when it was not their turn.',
            self::GAME_ALREADY_COMPLETED => 'Applies to: All games. Indicates that an action was attempted on a completed game.',
            self::GAME_NOT_ACTIVE => 'Applies to: All games. Indicates that an action was attempted on a game that is not active.',
            self::PLAYER_NOT_IN_GAME => 'Applies to: All games. Indicates that the user is not a participant in the game.',
            self::ACTION_TIMEOUT => 'Applies to: Timed games. Indicates that the time limit for the action has expired.',
            self::INVALID_STATE => 'Applies to: All games. Indicates a system error where the game state is invalid.',
            self::INVALID_ACTION_PARAMETERS => 'Applies to: All games. Indicates that the action parameters are malformed.',
            self::INVALID_ACTION_TYPE => 'Applies to: All games. Indicates that the action type is not supported.',
            self::INVALID_PHASE => 'Applies to: Phase-based games. Indicates that the action is not allowed in the current phase.',
            self::WAITING_FOR_OTHER_PLAYERS => 'Applies to: Simultaneous action games. Indicates that other players must act first.',
            self::RESOURCE_UNAVAILABLE => 'Applies to: Resource-based games. Indicates that required resources are missing.',
        };
    }

    public function message(): string
    {
        return match ($this) {
            self::NOT_PLAYER_TURN => 'It is not your turn.',
            self::GAME_ALREADY_COMPLETED => 'The game has already finished.',
            self::GAME_NOT_ACTIVE => 'The game is not currently active.',
            self::PLAYER_NOT_IN_GAME => 'You are not a player in this game.',
            self::ACTION_TIMEOUT => 'Time has run out for this action.',
            self::INVALID_STATE => 'Game state is invalid.',
            self::INVALID_ACTION_PARAMETERS => 'Invalid action parameters provided.',
            self::INVALID_ACTION_TYPE => 'This action is not supported.',
            self::INVALID_PHASE => 'Action not allowed in current game phase.',
            self::WAITING_FOR_OTHER_PLAYERS => 'Waiting for other players to complete their actions.',
            self::RESOURCE_UNAVAILABLE => 'Insufficient resources for this action.',
        };
    }

    public function isRetryable(): bool
    {
        return match ($this) {
            self::NOT_PLAYER_TURN,
            self::WAITING_FOR_OTHER_PLAYERS => true,
            default => false,
        };
    }
}
