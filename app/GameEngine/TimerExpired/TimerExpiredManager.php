<?php

declare(strict_types=1);

namespace App\GameEngine\TimerExpired;

use App\Enums\GameAttributes\GameTimer;
use App\GameEngine\TimerExpired\Drivers\ForfeitDriver;
use App\GameEngine\TimerExpired\Drivers\NoneDriver;
use App\GameEngine\TimerExpired\Drivers\PassDriver;
use Illuminate\Support\Manager;

class TimerExpiredManager extends Manager
{
    public function createForfeitDriver(): HandlerContract
    {
        return new ForfeitDriver();
    }

    public function createPassDriver(): HandlerContract
    {
        return new PassDriver();
    }

    public function createNoneDriver(): HandlerContract
    {
        return new NoneDriver();
    }

    public function getDefaultDriver(): string
    {
        return 'none';
    }

    public function getDriverFor(GameTimer $timer): HandlerContract
    {
        $driver = match ($timer) {
            GameTimer::FORFEIT => 'forfeit',
            GameTimer::PASS => 'pass',
            GameTimer::NONE => 'none',
        };

        return $this->driver($driver);
    }
}
