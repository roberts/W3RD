<?php

namespace App\Http\Controllers\Api\V1\Account;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponses;
use App\Services\Account\UserStatisticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecordsController extends Controller
{
    use ApiResponses;

    public function __construct(
        protected UserStatisticsService $statisticsService
    ) {}

    /**
     * Get performance records and statistics.
     *
     * GET /v1/account/records
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        return $this->dataResponse([
            ...$this->statisticsService->getGameStatistics($user),
            'win_rate' => $this->statisticsService->getWinRate($user),
            'total_points' => $this->statisticsService->getTotalPoints($user),
            'elo_ratings' => $this->statisticsService->getEloRatings($user),
            'global_rank' => $this->statisticsService->getGlobalRank($user),
        ]);
    }
}
