<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

/**
 * Exception thrown when action data is malformed or missing required fields.
 *
 * This is distinct from GameActionDeniedException - this handles structural
 * issues with the request (missing fields, wrong types), while GameActionDeniedException
 * handles game rule violations (invalid moves, not your turn, etc).
 *
 * HTTP Status: 400 Bad Request
 */
class InvalidActionDataException extends Exception
{
    /**
     * Create a new exception instance.
     *
     * @param  string  $message  Human-readable error message
     * @param  string  $errorCode  Machine-readable error code
     * @param  string  $gameTitle  Game title slug (e.g., 'validate-four', 'checkers')
     * @param  array<string, mixed>  $context  Additional error context
     */
    public function __construct(
        string $message,
        public readonly string $errorCode,
        public readonly string $gameTitle,
        public readonly array $context = []
    ) {
        parent::__construct($message);
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'error_code' => $this->errorCode,
            'game_title' => $this->gameTitle,
            'errors' => $this->context,
        ], 400);
    }
}
