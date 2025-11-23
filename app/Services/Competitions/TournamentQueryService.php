<?php

declare(strict_types=1);

namespace App\Services\Competitions;

use App\Models\Competitions\Tournament;
use Illuminate\Database\Eloquent\Builder;

class TournamentQueryService
{
    /**
     * Build a query for tournaments with filters applied.
     */
    public function buildTournamentQuery(array $filters): Builder
    {
        $query = Tournament::query()->with('users');

        // Apply status filter
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Apply game title filter
        if (isset($filters['game_title'])) {
            $query->where('game_title', $filters['game_title']);
        }

        // Apply format filter
        if (isset($filters['format'])) {
            $query->where('format', $filters['format']);
        }

        return $query->orderBy('starts_at', 'asc');
    }
}
