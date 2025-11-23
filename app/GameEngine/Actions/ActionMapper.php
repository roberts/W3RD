<?php

declare(strict_types=1);

namespace App\GameEngine\Actions;

use App\Exceptions\InvalidGameActionException;
use App\GameTitles\BaseGameTitle;

/**
 * Maps raw action input to validated action DTOs.
 *
 * This class wraps mode-specific action mappers to provide
 * uniform error handling and validation.
 */
class ActionMapper
{
    /**
     * Create a validated action DTO from raw input.
     *
     * @param  BaseGameTitle  $mode  The game mode handler
     * @param  string  $actionType  The action type identifier
     * @param  array  $actionDetails  Raw action data
     * @return object The validated action DTO
     *
     * @throws InvalidGameActionException
     */
    public function mapToAction(
        BaseGameTitle $mode,
        string $actionType,
        array $actionDetails
    ): object {
        try {
            $mapperClass = $mode->getActionMapper();

            return $mapperClass::create($actionType, $actionDetails);
        } catch (\InvalidArgumentException $e) {
            throw new InvalidGameActionException(
                "Invalid action '{$actionType}' for game",
                $actionType,
                $actionDetails,
                $e
            );
        } catch (\Exception $e) {
            throw new InvalidGameActionException(
                "Failed to map action '{$actionType}': {$e->getMessage()}",
                $actionType,
                $actionDetails,
                $e
            );
        }
    }
}
