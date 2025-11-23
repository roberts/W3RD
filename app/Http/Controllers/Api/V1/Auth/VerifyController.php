<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\VerifyRequest;
use App\Http\Traits\ApiResponses;
use App\Models\Auth\Registration;
use App\Models\Auth\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class VerifyController extends Controller
{
    use ApiResponses;

    /**
     * Verify email address with token from registration.
     *
     * POST /v1/auth/verify
     */
    public function __invoke(VerifyRequest $request): JsonResponse
    {
        $registration = Registration::where('verification_token', $request->token)->firstOrFail();

        // Create the user
        $user = User::create([
            'name' => $request->name ?? 'New User',
            'email' => $registration->email,
            'password' => $registration->password, // Already hashed
            'email_verified_at' => now(),
        ]);

        // Delete the registration record
        $registration->delete();

        // Create token for the user
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => 31536000,
            'user' => [
                'username' => $user->username,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'level' => $user->level ?? 1,
                'xp' => $user->xp ?? 0,
                'created_at' => $user->created_at->toIso8601String(),
            ],
        ], 200);
    }
}
