<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class StatusController extends Controller
{
    /**
     * Get the API health status.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
        ]);
    }
}
