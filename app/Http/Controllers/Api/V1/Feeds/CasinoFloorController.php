<?php

namespace App\Http\Controllers\Api\V1\Feeds;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponses;
use App\Services\Feeds\DataFeedService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CasinoFloorController extends Controller
{
    use ApiResponses;

    public function __construct(
        protected DataFeedService $feedService
    ) {}

    /**
     * SSE stream of tournament updates.
     */
    public function tournaments(Request $request): StreamedResponse
    {
        return response()->stream(
            function () {
                $this->feedService->streamTournamentUpdates();
            },
            200,
            [
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache',
                'X-Accel-Buffering' => 'no',
            ]
        );
    }

    /**
     * SSE stream of challenge activity.
     */
    public function challenges(Request $request): StreamedResponse
    {
        return response()->stream(
            function () {
                $this->feedService->streamChallengeActivity();
            },
            200,
            [
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache',
                'X-Accel-Buffering' => 'no',
            ]
        );
    }

    /**
     * SSE stream of achievement unlocks.
     */
    public function achievements(Request $request): StreamedResponse
    {
        $userId = $request->query('user_id');

        return response()->stream(
            function () use ($userId) {
                $this->feedService->streamAchievements($userId);
            },
            200,
            [
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache',
                'X-Accel-Buffering' => 'no',
            ]
        );
    }
}
