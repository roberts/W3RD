<?php

declare(strict_types=1);

namespace App\GameEngine\Managers;

use App\GameEngine\Interfaces\PacingDriver;

class PacingManager
{
    public function __construct(
        protected PacingDriver $driver
    ) {}

    public function getDriver(): PacingDriver
    {
        return $this->driver;
    }

    // Delegate common methods to the driver
    public function startTurnTimer($game): void
    {
        $this->driver->startTurnTimer($game);
    }

    public function validateActionTime($game): void
    {
        $this->driver->validateActionTime($game);
    }
}
