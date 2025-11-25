<?php

namespace App\DataTransferObjects\Competitions;

use App\Models\Auth\User;
use App\Models\Competitions\Tournament;
use App\Models\Competitions\TournamentUser;
use Spatie\LaravelData\Data;

class StandingData extends Data
{
    public function __construct(
        public int $user_id,
        public string $username,
        public string $status,
        public int $seed,
        public ?int $placement,
        public ?int $earnings,
    ) {}

    public static function fromUser(User $user, Tournament $tournament): self
    {
        /** @var TournamentUser $pivot */
        $pivot = $user->tournaments()
            ->where('tournament_id', $tournament->id)
            ->first()
            ->pivot;

        return new self(
            user_id: $user->id,
            username: $user->username,
            status: $pivot->status ?? 'unknown',
            seed: $pivot->seed ?? 0,
            placement: $pivot->placement,
            earnings: $pivot->earnings,
        );
    }
}
