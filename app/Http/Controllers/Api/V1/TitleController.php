<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class TitleController extends Controller
{
    /**
     * Get list of available game titles.
     */
    public function index(): JsonResponse
    {
        $titles = config('protocol.game_titles');

        return response()->json([
            'data' => $titles,
        ]);
    }
}
