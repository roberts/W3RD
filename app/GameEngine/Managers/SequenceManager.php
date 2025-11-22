<?php

declare(strict_types=1);

namespace App\GameEngine\Managers;

use App\GameEngine\Interfaces\SequenceDriver;

class SequenceManager
{
    public function __construct(
        protected SequenceDriver $driver
    ) {}

    public function getDriver(): SequenceDriver
    {
        return $this->driver;
    }

    // Delegate common methods to the driver
    public function isPlayerTurn($game, $player): bool
    {
        return $this->driver->isPlayerTurn($game, $player);
    }

    public function advanceTurn($game)
    {
        return $this->driver->advanceTurn($game);
    }
}
