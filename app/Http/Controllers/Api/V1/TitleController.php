<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\GameTitle;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;

class TitleController extends Controller
{
    use ApiResponses;
    /**
     * Get list of available game titles.
     */
    public function index(): JsonResponse
    {
        $titles = collect(GameTitle::cases())->map(function (GameTitle $title) {
            return [
                'key' => $title->value,
                'name' => $title->label(),
                'description' => $this->getDescription($title),
                'min_players' => $title->minPlayers(),
                'max_players' => $title->maxPlayers(),
            ];
        })->toArray();

        return $this->successResponse($titles);
    }

    /**
     * Get description for a game title.
     */
    private function getDescription(GameTitle $title): string
    {
        return match ($title) {
            GameTitle::VALIDATE_FOUR => 'Classic connect four game where players compete to align four pieces in a row.',
            GameTitle::CHECKERS => 'Classic board game where players move pieces diagonally, capturing opponent pieces by jumping over them.',
            GameTitle::HEARTS => 'Classic 4-player card game where the goal is to avoid taking hearts and the Queen of Spades, or shoot the moon to score big.',
            GameTitle::SPADES => 'Classic 4-player trick-taking card game played in partnerships.',
        };
    }
}
