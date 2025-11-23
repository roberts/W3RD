<?php

declare(strict_types=1);

namespace App\Services\Games;

use App\Models\Games\Action;
use App\Models\Games\Game;
use Illuminate\Support\Collection;

class GameTimelineService
{
    /**
     * Get recent actions for a game.
     */
    public function getRecentActions(Game $game, int $count): Collection
    {
        return Action::where('game_id', $game->id)
            ->with('player.user:id,name,username')
            ->orderBy('created_at', 'desc')
            ->limit($count)
            ->get()
            ->reverse()
            ->values();
    }
}
