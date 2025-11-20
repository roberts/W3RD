<?php

namespace App\Http\Controllers\Api\V1\Library;

use App\DataTransferObjects\Library\GameLibraryData;
use App\Enums\GameTitle;
use App\Http\Controllers\Controller;
use App\Services\GameLibraryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GameLibraryController extends Controller
{
    public function __construct(
        private GameLibraryService $libraryService
    ) {}

    /**
     * Get list of available game titles with filtering.
     *
     * GET /v1/library?pacing=turn-based&players=2&category=strategy
     */
    public function index(Request $request): JsonResponse
    {
        $games = $this->libraryService->getGames(
            pacing: $request->query('pacing'),
            playerCount: $request->query('players'),
            category: $request->query('category')
        );

        return response()->json([
            'data' => $games,
            'total' => count($games),
        ]);
    }

    /**
     * Get detailed metadata for a specific game.
     *
     * GET /v1/library/{key}
     */
    public function show(string $key): JsonResponse
    {
        $game = $this->libraryService->getGameDetails($key);

        return response()->json([
            'data' => $game,
        ]);
    }

    /**
     * Get entity definitions for a specific game (cards, units, boards, etc).
     *
     * GET /v1/library/{key}/entities
     */
    public function entities(string $key): JsonResponse
    {
        $entities = $this->libraryService->getGameEntities($key);

        return response()->json([
            'data' => $entities,
            'cacheable' => true,
            'cache_duration_seconds' => 3600,
        ]);
    }
}
