<?php

declare(strict_types=1);

namespace App\GameEngine\Managers;

use App\Enums\GameAttributes\GameVisibility;
use App\GameEngine\Interfaces\VisibilityDriver;

class VisibilityManager
{
    public function __construct(
        protected VisibilityDriver $driver
    ) {}

    public function getDriver(): VisibilityDriver
    {
        return $this->driver;
    }

    // Delegate common methods to the driver
    public function redact(object $gameState, $player): object
    {
        return $this->driver->redact($gameState, $player);
    }
}
