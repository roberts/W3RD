<?php

declare(strict_types=1);

namespace App\GameEngine\Interfaces;

interface GameConfigContract
{
    /**
     * Get the mapping of Action classes to their Handlers and configuration.
     *
     * Returns an array where keys are Action class names and values are arrays containing:
     * - 'handler': The Handler class name
     * - 'label': A human-readable label for the action (e.g., "Drop Disc")
     * - 'rules': (Optional) Additional configuration for the handler
     *
     * @return array<class-string, array<string, mixed>>
     */
    public function getActionRegistry(): array;

    /**
     * Get the initial state configuration.
     *
     * @return array<string, mixed>
     */
    public function getInitialStateConfig(): array;
}
