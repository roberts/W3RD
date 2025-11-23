<?php

namespace App\Services;

use App\Actions\Auth\TrackAuthenticationEntryAction;
use App\DataTransferObjects\Auth\AuthResult;
use App\Models\Auth\Registration;
use App\Models\Auth\SocialAccount;
use App\Models\Auth\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthService
{
    public function __construct(
        protected TrackAuthenticationEntryAction $trackEntry
    ) {}

    /**
     * Authenticate a user with email and password.
     */
    public function authenticateUser(string $email, string $password, Request $request): AuthResult
    {
        if (! Auth::attempt(['email' => $email, 'password' => $password])) {
            return AuthResult::failed('The provided credentials are incorrect');
        }

        $user = Auth::user();
        $token = $user->createToken($request->ip() ?? 'api-token');

        $this->trackEntry->execute(
            $user,
            $token,
            $request->header('X-Client-Key'),
            $request->ip(),
            $request->userAgent()
        );

        return AuthResult::success($token->plainTextToken, $user);
    }

    /**
     * Create a new registration pending email verification.
     */
    public function createRegistration(int $clientId, string $email, string $hashedPassword): Registration
    {
        return Registration::create([
            'client_id' => $clientId,
            'email' => $email,
            'password' => $hashedPassword,
            'verification_token' => Str::random(60),
        ]);
    }

    /**
     * Complete user verification and create user account.
     */
    public function verifyRegistration(string $token): array
    {
        $registration = Registration::where('verification_token', $token)->firstOrFail();

        // Create the user
        $user = User::create([
            'name' => 'New User',
            'email' => $registration->email,
            'password' => $registration->password,
            'registration_client_id' => $registration->client_id,
        ]);

        // Link registration to user
        $registration->update(['user_id' => $user->id]);

        $token = $user->createToken('api-token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * Handle social authentication (find or create user and social account).
     */
    public function handleSocialLogin(
        string $provider,
        string $providerId,
        string $providerToken,
        string $email,
        string $name,
        int $clientId
    ): array {
        // Find or create the social account
        $socialAccount = SocialAccount::firstOrCreate(
            [
                'provider_name' => $provider,
                'provider_id' => $providerId,
            ],
            [
                'provider_token' => $providerToken,
                'provider_refresh_token' => null,
            ]
        );

        // Find or create the user
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make(Str::random(24)),
                'registration_client_id' => $clientId,
            ]
        );

        // Link user and social account if not already linked
        if ($socialAccount->user_id !== $user->id) {
            $socialAccount->user_id = $user->id;
            $socialAccount->save();
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }
}
