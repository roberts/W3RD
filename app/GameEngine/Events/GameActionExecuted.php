<?php

namespace App\GameEngine\Events;

use App\Models\Games\Action;
use App\Models\Games\Game;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GameActionExecuted implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Game $game,
        public Action $action
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
            'action_id' => $this->action->id,
            'action_type' => $this->action->action_type,
            'player_ulid' => $this->action->player->ulid,
            'turn_number' => $this->action->turn_number,
            'created_at' => $this->action->created_at->toIso8601String(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'action.executed';
    }
}
