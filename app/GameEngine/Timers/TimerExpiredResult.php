<?php

declare(strict_types=1);

namespace App\GameEngine\Timers;

use App\GameEngine\GameOutcome;
use Illuminate\Http\JsonResponse;

class TimerExpiredResult
{
    public function __construct(
        public bool $hasExpired,
        public ?JsonResponse $errorResponse = null,
        public ?GameOutcome $outcome = null
    ) {}
}
