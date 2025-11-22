<?php

declare(strict_types=1);

namespace App\GameEngine\Kernel;

use App\Enums\GameErrorCode;
use App\GameEngine\Interfaces\GameActionHandlerInterface;
use App\GameEngine\Interfaces\GameConfigContract;
use App\GameEngine\Interfaces\GameTitleContract;
use App\GameEngine\ValidationResult;
use App\Models\Auth\User;
use App\Models\Game\Game;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

class GameKernel
{
    /** @var array<string, GameActionHandlerInterface> */
    protected array $handlers = [];

    public function __construct(
        private GameConfigContract $config,
        public GameTitleContract $gameTitle,
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

    public function validatePlayerAction(Game $game, object $gameState, User $player, object $action): ValidationResult
    {
        // 1. Pacing Check (Is player too slow?)
        $this->gameTitle->validateActionTime($game);

        // 2. Sequence Check (Is it the player's turn?)
        if (! $this->gameTitle->isPlayerTurn($game, $player)) {
            return ValidationResult::invalid(
                GameErrorCode::NOT_PLAYER_TURN->value,
                'It is not your turn to act.'
            );
        }

        // 3. Game-specific action validation
        return $this->validateAction($gameState, $action);
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

    public function advanceGame(Game $game): Game
    {
        $game = $this->gameTitle->advanceTurn($game);
        $this->gameTitle->startTurnTimer($game);

        return $game;
    }

    public function redactStateForPlayer(object $gameState, User $player): object
    {
        return $this->gameTitle->redact($gameState, $player);
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
