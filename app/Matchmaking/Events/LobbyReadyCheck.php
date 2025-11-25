<?php

namespace App\Matchmaking\Events;

use App\Models\Matchmaking\Lobby;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LobbyReadyCheck implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Lobby $lobby
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('Lobby.'.$this->lobby->id),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'message' => 'Ready check initiated',
            'lobby_ulid' => $this->lobby->ulid,
        ];
    }
}
