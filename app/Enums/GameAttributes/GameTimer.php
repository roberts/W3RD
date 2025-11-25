<?php

declare(strict_types=1);

namespace App\Enums\GameAttributes;

enum GameTimer: string
{
    case FORFEIT = 'forfeit';
    case PASS = 'pass';
    case NONE = 'none';
}
