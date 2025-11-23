<?php

namespace App\Events;

use App\Models\Competitions\Tournament;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TournamentBracketUpdated implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Tournament $tournament
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel("tournaments.{$this->tournament->ulid}");
    }

    public function broadcastWith(): array
    {
        return [
            'tournament_ulid' => $this->tournament->ulid,
            'status' => $this->tournament->status,
            'bracket_data' => $this->tournament->bracket_data,
            'updated_at' => $this->tournament->updated_at->toIso8601String(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'bracket.updated';
    }
}
