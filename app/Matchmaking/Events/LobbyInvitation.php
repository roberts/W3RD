<?php

namespace App\Matchmaking\Events;

use App\Models\Auth\User;
use App\Models\Matchmaking\Lobby;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LobbyInvitation implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $userId,
        public Lobby $lobby
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('App.Models.User.'.$this->userId),
        ];
    }

    public function broadcastWith(): array
    {
        /** @var User $host */
        $host = $this->lobby->host;

        return [
            'lobby' => [
                'ulid' => $this->lobby->ulid,
                'game_title' => $this->lobby->title_slug->value,
                'game_mode' => $this->lobby->mode?->slug,
                'host' => [
                    'id' => $host->id,
                    'name' => $host->name,
                    'username' => $host->username,
                ],
                'is_public' => $this->lobby->is_public,
                'min_players' => $this->lobby->min_players,
                'scheduled_at' => $this->lobby->scheduled_at?->toIso8601String(),
            ],
        ];
    }
}
