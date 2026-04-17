<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}

    /**
     * Register a new user account.
     *
     * POST /v1/auth/register
     */
    public function __invoke(RegisterRequest $request): JsonResponse
    {
        $registration = $this->authService->createRegistration(
            clientId: $request->client_id,
            email: $request->email,
            hashedPassword: Hash::make($request->password)
        );

        // In production, dispatch email verification job
        // dispatch(new SendVerificationEmail($registration));

        return response()->json([
            'message' => 'Registration successful. Please check your email to verify your account.',
            'registration_id' => $registration->uuid,
        ], 201);
    }
}
