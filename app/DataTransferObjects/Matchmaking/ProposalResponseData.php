<?php

namespace App\DataTransferObjects\Matchmaking;

use App\Matchmaking\Results\ProposalResult;

class ProposalResponseData
{
    /**
     * Create response data from a proposal result.
     *
     * @return array<string, mixed>
     */
    public static function fromResult(ProposalResult $result): array
    {
        $data = ProposalData::fromModel($result->proposal)->toArray();

        // Include new game ULID if a game was created
        if ($result->game !== null) {
            $data['new_game_ulid'] = $result->game->ulid;
        }

        return $data;
    }
}
