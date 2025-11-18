<?php

namespace App\Actions\Lobby;

use App\Models\Game\Lobby;

class FindLobbyByUlidAction
{
    /**
     * Find a lobby by its ULID.
     *
     * @param  string  $ulid
     * @param  array  $with  Optional relationships to eager load
     * @return Lobby
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function execute(string $ulid, array $with = []): Lobby
    {
        $query = Lobby::where('ulid', $ulid);

        if (! empty($with)) {
            $query->with($with);
        }

        return $query->firstOrFail();
    }
}
