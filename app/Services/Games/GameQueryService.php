<?php

namespace App\Services\Games;

use App\Models\Auth\User;
use App\Models\Games\Game;
use Illuminate\Database\Eloquent\Builder;

class GameQueryService
{
    /**
     * Build a query for user games with filters applied.
     */
    public function buildUserGamesQuery(User $user, array $filters): Builder
    {
        $query = Game::forUser($user->id)
            ->with(['players.user.avatar.image', 'mode']);

        // Apply status filter
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Apply game title filter
        if (isset($filters['game_title'])) {
            $query->whereHas('mode', function ($q) use ($filters) {
                $q->where('title_slug', $filters['game_title']);
            });
        }

        // Apply date from filter
        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        // Apply date to filter
        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        // Apply opponent username filter
        if (isset($filters['opponent_username'])) {
            $query->whereHas('players.user', function ($q) use ($filters) {
                $q->where('username', $filters['opponent_username']);
            });
        }

        return $query->orderBy('updated_at', 'desc');
    }
}
