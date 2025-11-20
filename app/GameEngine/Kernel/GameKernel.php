<?php

declare(strict_types=1);

namespace App\GameEngine\Kernel;

use App\Enums\GameErrorCode;
use App\GameEngine\Interfaces\GameActionHandlerInterface;
use App\GameEngine\Interfaces\GameConfigContract;
use App\GameEngine\ValidationResult;
use InvalidArgumentException;

class GameKernel
{
    /** @var array<string, GameActionHandlerInterface> */
    protected array $handlers = [];

    public function __construct(
        protected GameConfigContract $config
    ) {
        $this->initializeHandlers();
    }

    protected function initializeHandlers(): void
    {
        foreach ($this->config->getActionRegistry() as $actionClass => $config) {
            $handlerClass = $config['handler'];
            if (! class_exists($handlerClass)) {
                throw new InvalidArgumentException("Handler class {$handlerClass} not found.");
            }
            // In a real app, we might use the container to resolve this to allow dependency injection
            $this->handlers[$actionClass] = new $handlerClass($config['rules'] ?? []);
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
                // We use the class name (or a slug if we had one) as the key
                // For the API, we might want to map this to a cleaner string key
                // But for now, let's use the class basename or a key from config if we add it
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
