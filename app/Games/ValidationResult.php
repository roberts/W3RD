<?php

declare(strict_types=1);

namespace App\Games;

/**
 * Result of action validation.
 *
 * Provides rich feedback about whether an action is valid, including
 * detailed error information for the UI when invalid.
 *
 * Example usage:
 * ```php
 * if ($action->column >= $state->columns) {
 *     return ValidationResult::invalid(
 *         'INVALID_COLUMN',
 *         'Column must be between 0 and ' . ($state->columns - 1),
 *         ['column' => $action->column, 'max' => $state->columns - 1]
 *     );
 * }
 * return ValidationResult::valid();
 * ```
 */
class ValidationResult
{
    /**
     * Create a new validation result.
     *
     * @param bool $isValid Whether the action is valid
     * @param string|null $errorCode Machine-readable error code (e.g., 'INVALID_COLUMN', 'COLUMN_FULL')
     * @param string|null $message Human-readable error message for display
     * @param array<string, mixed> $context Additional context about the error
     */
    public function __construct(
        public readonly bool $isValid,
        public readonly ?string $errorCode = null,
        public readonly ?string $message = null,
        public readonly array $context = [],
    ) {}

    /**
     * Create a successful validation result.
     *
     * @return self
     */
    public static function valid(): self
    {
        return new self(true);
    }

    /**
     * Create a failed validation result.
     *
     * @param string $errorCode Machine-readable error code
     * @param string $message Human-readable error message
     * @param array<string, mixed> $context Additional context about the error
     * @return self
     */
    public static function invalid(string $errorCode, string $message, array $context = []): self
    {
        return new self(false, $errorCode, $message, $context);
    }

    /**
     * Convert to array for API responses.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'is_valid' => $this->isValid,
            'error_code' => $this->errorCode,
            'message' => $this->message,
            'context' => $this->context,
        ];
    }
}
