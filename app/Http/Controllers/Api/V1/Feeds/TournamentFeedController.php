<?php

namespace App\Http\Controllers\Api\V1\Feeds;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponses;
use App\Services\Feeds\DataFeedService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TournamentFeedController extends Controller
{
    use ApiResponses;

    public function __construct(
        protected DataFeedService $feedService
    ) {}

    /**
     * SSE stream of tournament updates.
     */
    public function show(Request $request): StreamedResponse
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
}
