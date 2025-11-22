<?php

declare(strict_types=1);

namespace App\GameEngine\Kernel;

use App\Enums\GameErrorCode;
use App\GameEngine\Interfaces\GameActionHandlerInterface;
use App\GameEngine\Interfaces\GameConfigContract;
use App\GameEngine\ValidationResult;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

/**
 * GameKernel: Lean action handler registry for managing game actions.
 *
 * Responsibilities:
 * - Initialize and manage action handlers from config
 * - Validate actions using appropriate handlers
 * - Apply actions to game state
 * - Aggregate available actions across all handlers
 *
 * Does NOT handle:
 * - Validation orchestration (pacing/sequence checks) - BaseGameTitle does this
 * - Turn advancement - TurnManager handles this
 * - State redaction - BaseGameTitle delegates to traits
 */
class GameKernel
{
    /** @var array<string, GameActionHandlerInterface> */
    protected array $handlers = [];

    public function __construct(
        private GameConfigContract $config,
        private ?Container $container = null,
    ) {
        $this->container = $container ?? app();
        $this->initializeHandlers();
    }

    protected function initializeHandlers(): void
    {
        foreach ($this->config->getActionRegistry() as $actionClass => $config) {
            $handlerClass = $config['handler'];
            if (! class_exists($handlerClass)) {
                throw new InvalidArgumentException("Handler class {$handlerClass} not found.");
            }

            // Use container to resolve handlers, allowing dependency injection
            $rules = $config['rules'] ?? [];
            $this->handlers[$actionClass] = $this->container->makeWith($handlerClass, ['rules' => $rules]);
        }
    }

    public function validateAction(object $state, object $action): ValidationResult
    {
        $actionClass = get_class($action);
        $handler = $this->handlers[$actionClass] ?? null;

        if (! $handler) {
            return ValidationResult::invalid(
                GameErrorCode::INVALID_ACTION_TYPE->value,
                "Action type {$actionClass} is not supported by this game configuration."
            );
        }

        return $handler->validate($state, $action);
    }

    public function applyAction(object $state, object $action): object
    {
        $actionClass = get_class($action);
        $handler = $this->handlers[$actionClass] ?? null;

        if (! $handler) {
            return $state;
        }

        return $handler->apply($state, $action);
    }

    public function getAvailableActions(object $state, string $playerUlid): array
    {
        $options = [];

        foreach ($this->config->getActionRegistry() as $actionClass => $config) {
            $handler = $this->handlers[$actionClass];
            $actionOptions = $handler->getAvailableOptions($state, $playerUlid);

            if (! empty($actionOptions)) {
                $key = $this->getActionKey($actionClass);
                $options[$key] = $actionOptions;
            }
        }

        return $options;
    }

    public function getConfig(): GameConfigContract
    {
        return $this->config;
    }

    protected function getActionKey(string $actionClass): string
    {
        // Convert App\GameEngine\Actions\PlacePiece to place_piece
        return strtolower(class_basename($actionClass));
    }
}
