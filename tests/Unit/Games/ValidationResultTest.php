<?php

declare(strict_types=1);

use App\GameEngine\ValidationResult;

describe('ValidationResult', function () {
    describe('Factory Methods', function () {
        test('valid creates successful result', function () {
            $result = ValidationResult::valid();

            expect($result->isValid)->toBeTrue()
                ->and($result->errorCode)->toBeNull()
                ->and($result->message)->toBeNull()
                ->and($result->context)->toBe([]);
        });

        test('invalid creates failed result with error code', function () {
            $result = ValidationResult::invalid('INVALID_COLUMN', 'Column is out of bounds');

            expect($result->isValid)->toBeFalse()
                ->and($result->errorCode)->toBe('INVALID_COLUMN')
                ->and($result->message)->toBe('Column is out of bounds')
                ->and($result->context)->toBe([]);
        });

        test('invalid supports context data', function () {
            $result = ValidationResult::invalid(
                'COLUMN_FULL',
                'The selected column is full',
                ['column' => 3, 'max_pieces' => 6]
            );

            expect($result->isValid)->toBeFalse()
                ->and($result->errorCode)->toBe('COLUMN_FULL')
                ->and($result->message)->toBe('The selected column is full')
                ->and($result->context)->toBe(['column' => 3, 'max_pieces' => 6]);
        });
    });

    describe('Properties', function () {
        test('valid result has no error information', function () {
            $result = ValidationResult::valid();

            expect($result->errorCode)->toBeNull()
                ->and($result->message)->toBeNull()
                ->and($result->context)->toBeEmpty();
        });

        test('invalid result requires error code and message', function () {
            $result = ValidationResult::invalid('ERROR_CODE', 'Error message');

            expect($result->errorCode)->not->toBeNull()
                ->and($result->message)->not->toBeNull();
        });

        test('context is optional for invalid results', function () {
            $withContext = ValidationResult::invalid('CODE', 'Message', ['key' => 'value']);
            $withoutContext = ValidationResult::invalid('CODE', 'Message');

            expect($withContext->context)->not->toBeEmpty()
                ->and($withoutContext->context)->toBeEmpty();
        });
    });

    describe('Serialization', function () {
        test('toArray includes all properties for valid result', function () {
            $result = ValidationResult::valid();

            $array = $result->toArray();

            expect($array)->toBe([
                'is_valid' => true,
                'error_code' => null,
                'message' => null,
                'context' => [],
            ]);
        });

        test('toArray includes all properties for invalid result', function () {
            $result = ValidationResult::invalid(
                'NOT_PLAYER_TURN',
                'It is not your turn',
                ['current_player' => 'player-123', 'your_id' => 'player-456']
            );

            $array = $result->toArray();

            expect($array)->toBe([
                'is_valid' => false,
                'error_code' => 'NOT_PLAYER_TURN',
                'message' => 'It is not your turn',
                'context' => [
                    'current_player' => 'player-123',
                    'your_id' => 'player-456',
                ],
            ]);
        });
    });

    describe('Common Error Codes', function () {
        test('handles column validation errors', function () {
            $result = ValidationResult::invalid(
                'INVALID_COLUMN',
                'Column must be between 0 and 6',
                ['column' => 7, 'max' => 6]
            );

            expect($result->errorCode)->toBe('INVALID_COLUMN')
                ->and($result->context)->toHaveKey('column')
                ->and($result->context)->toHaveKey('max');
        });

        test('handles turn validation errors', function () {
            $result = ValidationResult::invalid(
                'NOT_PLAYER_TURN',
                'It is not your turn to play'
            );

            expect($result->errorCode)->toBe('NOT_PLAYER_TURN')
                ->and($result->isValid)->toBeFalse();
        });

        test('handles game state errors', function () {
            $result = ValidationResult::invalid(
                'GAME_COMPLETED',
                'Cannot perform action on completed game',
                ['game_status' => 'COMPLETED']
            );

            expect($result->errorCode)->toBe('GAME_COMPLETED')
                ->and($result->context['game_status'])->toBe('COMPLETED');
        });

        test('handles piece validation errors', function () {
            $result = ValidationResult::invalid(
                'INVALID_PIECE',
                'Selected piece does not belong to you',
                ['piece_owner' => 'player-1', 'your_id' => 'player-2']
            );

            expect($result->errorCode)->toBe('INVALID_PIECE')
                ->and($result->isValid)->toBeFalse();
        });
    });

    describe('Readonly Properties', function () {
        test('properties are readonly', function () {
            $result = ValidationResult::valid();

            expect($result)->toHaveProperty('isValid')
                ->and($result)->toHaveProperty('errorCode')
                ->and($result)->toHaveProperty('message')
                ->and($result)->toHaveProperty('context');
        });
    });
});
