<?php

declare(strict_types=1);

namespace App\GameEngine\Results;

use App\GameEngine\ValidationResult;

/**
 * Result object returned by GameKernel after processing an action.
 *
 * The kernel is a pure function that validates and applies actions.
 * This result encapsulates the outcome without side effects.
 */
readonly class ActionKernelResult
{
    private function __construct(
        public bool $isValid,
        public ?object $newState,
        public ?ValidationResult $validationResult,
    ) {}

    /**
     * Create a successful result with the new game state.
     */
    public static function valid(object $newState): self
    {
        return new self(
            isValid: true,
            newState: $newState,
            validationResult: null,
        );
    }

    /**
     * Create a failed result with validation errors.
     */
    public static function invalid(ValidationResult $validationResult): self
    {
        return new self(
            isValid: false,
            newState: null,
            validationResult: $validationResult,
        );
    }
}
