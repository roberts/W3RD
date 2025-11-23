<?php

namespace App\Events;

use App\Models\Games\Game;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GameStarted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Game $game
    ) {}

    public function broadcastOn(): array
    {
        $channels = [];

        // Broadcast to each player
        foreach ($this->game->players as $player) {
            $channels[] = new Channel("user.{$player->user_id}");
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'game.started';
    }

    public function broadcastWith(): array
    {
        return [
            'game_ulid' => $this->game->ulid,
            'game_title' => $this->game->title_slug->value,
            'status' => $this->game->status->value,
            'players' => $this->game->players->map(fn ($player) => [
                'user_id' => $player->user_id,
                'position_id' => $player->position_id,
                'color' => $player->color,
            ])->toArray(),
        ];
    }
}
