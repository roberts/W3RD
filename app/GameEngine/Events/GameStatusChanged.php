<?php

namespace App\GameEngine\Events;

use App\Models\Games\Game;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GameStatusChanged implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Game $game
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel("games.{$this->game->ulid}");
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'game_ulid' => $this->game->ulid,
            'status' => $this->game->status->value,
            'winner_id' => $this->game->winner_id,
            'outcome_type' => $this->game->outcome_type?->value,
            'completed_at' => $this->game->completed_at?->toIso8601String(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'status.changed';
    }
}
