<?php

namespace App\Actions\Game;

use App\Models\Game\Game;

class FindGameByUlidAction
{
    /**
     * Find a game by its ULID.
     *
     * @param  array  $with  Optional relationships to eager load
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function execute(string $ulid, array $with = []): Game
    {
        $query = Game::where('ulid', $ulid);

        if (! empty($with)) {
            $query->with($with);
        }

        return $query->firstOrFail();
    }
}
