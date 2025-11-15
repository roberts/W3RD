<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Game\Game;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GameActionProcessed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly Game $game,
        public readonly string $actionType,
        public readonly array $actionDetails,
        public readonly string $playerUlid,
    ) {
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel
     */
    public function broadcastOn(): Channel
    {
        return new Channel("game.{$this->game->ulid}");
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'action.processed';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'game_ulid' => $this->game->ulid,
            'action_type' => $this->actionType,
            'action_details' => $this->actionDetails,
            'player_ulid' => $this->playerUlid,
            'game_state' => $this->game->game_state,
            'status' => $this->game->status,
            'current_player_ulid' => $this->game->game_state['currentPlayerUlid'] ?? null,
            'winner_ulid' => $this->game->game_state['winnerUlid'] ?? null,
            'is_draw' => $this->game->game_state['isDraw'] ?? false,
            'timestamp' => now()->toISOString(),
        ];
    }
}
