<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;

class LoginController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {}

    /**
     * Authenticate user with email and password.
     *
     * POST /v1/auth/login
     */
    public function __invoke(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->authenticateUser(
            $request->email,
            $request->password,
            $request
        );

        if (! $result->success) {
            return response()->json([
                'error' => 'INVALID_CREDENTIALS',
                'message' => $result->errorMessage,
            ], 401);
        }

        return response()->json([
            'token' => $result->token,
            'user' => UserResource::make($result->user),
        ]);
    }
}
