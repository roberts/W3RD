<?php

namespace App\Matchmaking\Events;

use App\Models\Auth\User;
use App\Models\Game\Lobby;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LobbyPlayerJoined implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Lobby $lobby,
        public User $user
    ) {}

    public function broadcastOn(): PresenceChannel
    {
        return new PresenceChannel("lobby.{$this->lobby->ulid}");
    }

    public function broadcastWith(): array
    {
        return [
            'lobby_ulid' => $this->lobby->ulid,
            'user' => [
                'id' => $this->user->id,
                'username' => $this->user->username,
            ],
        ];
    }
}
