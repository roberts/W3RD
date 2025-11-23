<?php

declare(strict_types=1);

namespace App\Services\Matchmaking;

use App\Models\Matchmaking\Lobby;
use Illuminate\Database\Eloquent\Builder;

class LobbyQueryService
{
    /**
     * Build a query for lobbies with filters applied.
     */
    public function buildLobbyQuery(array $filters): Builder
    {
        $query = Lobby::with(['host.avatar.image', 'players.user.avatar.image']);

        // Apply public filter
        if (isset($filters['is_public'])) {
            $query->where('is_public', $filters['is_public']);
        } else {
            $query->where('is_public', true);
        }

        // Apply game title filter
        if (isset($filters['game_title'])) {
            $query->whereHas('mode', function ($q) use ($filters) {
                $q->where('title_slug', $filters['game_title']);
            });
        }

        // Apply status filter
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        } else {
            $query->pending();
        }

        return $query->latest();
    }
}
