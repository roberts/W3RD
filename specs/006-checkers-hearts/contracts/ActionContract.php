<?php

namespace App\Games\Contracts;

use Spatie\LaravelData\Contracts\DataObject;

interface ActionContract extends DataObject
{
    /**
     * Get the ULID of the player who performed the action.
     */
    public function getPlayerUlid(): string;
}
