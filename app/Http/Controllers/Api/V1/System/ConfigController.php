<?php

namespace App\Http\Controllers\Api\V1\System;

use App\Http\Controllers\Controller;
use App\Models\Title;
use Illuminate\Http\JsonResponse;

class ConfigController extends Controller
{
    /**
     * Get global platform configuration.
     *
     * @return JsonResponse
     */
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'api_version' => 'v1',
            'platform_name' => config('app.name'),
            'features' => [
                'authentication_providers' => ['email', 'google', 'apple', 'social'],
                'real_time_games' => true,
                'turn_based_games' => true,
                'tournaments' => true,
                'virtual_economy' => true,
                'leaderboards' => true,
                'sse_feeds' => true,
            ],
            'supported_games' => collect(\App\Enums\GameTitle::cases())->map(fn ($title) => [
                'key' => $title->value,
                'name' => $title->label(),
                'min_players' => $title->minPlayers(),
                'max_players' => $title->maxPlayers(),
            ]),
            'limits' => [
                'max_concurrent_games' => 10,
                'max_lobby_players' => 8,
                'action_timeout_seconds' => 300,
            ],
            'maintenance' => [
                'scheduled' => false,
                'message' => null,
            ],
        ]);
    }
}
