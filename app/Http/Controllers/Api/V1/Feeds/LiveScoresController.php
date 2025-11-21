<?php

namespace App\Http\Controllers\Api\V1\Feeds;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponses;
use App\Services\DataFeedService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LiveScoresController extends Controller
{
    use ApiResponses;

    public function __construct(
        protected DataFeedService $feedService
    ) {}

    /**
     * SSE stream of live game scores and updates.
     */
    public function games(Request $request): StreamedResponse
    {
        $gameTitle = $request->query('game_title');

        return response()->stream(
            function () use ($gameTitle) {
                $this->feedService->streamGameActivity($gameTitle);
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
     * SSE stream of win announcements.
     */
    public function wins(Request $request): StreamedResponse
    {
        $gameTitle = $request->query('game_title');

        return response()->stream(
            function () use ($gameTitle) {
                $this->feedService->streamWinAnnouncements($gameTitle);
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
