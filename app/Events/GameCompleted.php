<?php

namespace App\Events;

use App\Models\Game\Game;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GameCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Game $game,
        public ?string $winnerUlid = null,
        public bool $isDraw = false
    ) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        $channels = [];

        // Broadcast to each player
        foreach ($this->game->players as $player) {
            $channels[] = new Channel("user.{$player->user_id}");
        }

        return $channels;
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'game.completed';
    }

    /**
     * The data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'game_ulid' => $this->game->ulid,
            'game_title' => $this->game->title_slug->value,
            'status' => $this->game->status->value,
            'winner_ulid' => $this->winnerUlid,
            'is_draw' => $this->isDraw,
            'finished_at' => $this->game->finished_at?->toIso8601String(),
        ];
    }
}
