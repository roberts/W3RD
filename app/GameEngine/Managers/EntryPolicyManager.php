<?php

declare(strict_types=1);

namespace App\GameEngine\Managers;

use App\Enums\GameAttributes\GameEntryPolicy;
use App\GameEngine\Drivers\EntryPolicy\DropInDropOutDriver;
use App\GameEngine\Drivers\EntryPolicy\LockedOnStartDriver;
use App\GameEngine\Drivers\EntryPolicy\ReplacementOnlyDriver;
use App\GameEngine\Interfaces\EntryPolicyDriver;
use InvalidArgumentException;

class EntryPolicyManager
{
    public static function make(GameEntryPolicy $policy): EntryPolicyDriver
    {
        return match ($policy) {
            GameEntryPolicy::LOCKED_ON_START => new LockedOnStartDriver,
            GameEntryPolicy::DROP_IN_DROP_OUT => new DropInDropOutDriver,
            GameEntryPolicy::REPLACEMENT_ONLY => new ReplacementOnlyDriver,
            default => throw new InvalidArgumentException("Unsupported game entry policy: {$policy->value}"),
        };
    }
}
