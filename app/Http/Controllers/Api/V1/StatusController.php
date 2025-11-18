<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;

class StatusController extends Controller
{
    use ApiResponses;

    /**
     * Get the API health status.
     */
    public function index(): JsonResponse
    {
        return $this->successResponse(['status' => 'ok']);
    }
}
