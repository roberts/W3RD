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
        public bool $isDraw = false,
        public array $outcomeDetails = []
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
        $data = [
            'game_ulid' => $this->game->ulid,
            'game_title' => $this->game->title_slug->value,
            'status' => $this->game->status->value,
            'winner_ulid' => $this->winnerUlid,
            'is_draw' => $this->isDraw,
            'finished_at' => $this->game->finished_at?->toIso8601String(),
        ];

        // Add detailed outcome information if available
        if (!empty($this->outcomeDetails)) {
            $data['outcome'] = $this->outcomeDetails;
            
            // Add human-readable summary
            $data['summary'] = $this->generateSummary();
        }

        return $data;
    }

    /**
     * Generate a human-readable summary of the game outcome.
     */
    protected function generateSummary(): string
    {
        if ($this->isDraw) {
            return sprintf(
                'Game ended in a draw. %s',
                $this->outcomeDetails['finish_details']['reason_text'] ?? 'No winner'
            );
        }

        if ($this->winnerUlid && isset($this->outcomeDetails['winner'])) {
            $winner = $this->outcomeDetails['winner'];
            return sprintf(
                '%s won the game! %s',
                $winner['username'] ?? 'Player',
                $this->outcomeDetails['finish_details']['reason_text'] ?? ''
            );
        }

        return 'Game completed';
    }
}
