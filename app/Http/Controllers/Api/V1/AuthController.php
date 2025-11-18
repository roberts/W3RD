<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\SocialLoginRequest;
use App\Http\Requests\Auth\UpdateUserRequest;
use App\Http\Requests\Auth\VerifyRequest;
use App\Http\Resources\UserResource;
use App\Http\Traits\ApiResponses;
use App\Models\Auth\Entry;
use App\Models\Auth\Registration;
use App\Models\Auth\SocialAccount;
use App\Models\Auth\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    use ApiResponses;

    /**
     * Register a new user.
     */
    public function register(RegisterRequest $request)
    {
        $registration = Registration::create([
            'client_id' => $request->client_id,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'verification_token' => Str::random(60),
        ]);

        // In production, dispatch a job to send an email.
        // Mail::to($registration->email)->send(new VerifyEmail($registration->verification_token));

        return $this->createdResponse(
            null,
            'Registration successful. Please check your email to verify your account.'
        );
    }

    /**
     * Verify a user's email address.
     */
    public function verify(VerifyRequest $request)
    {
        $registration = Registration::where('verification_token', $request->token)->firstOrFail();

        // Create the user
        $user = User::create([
            'name' => 'New User', // Or get from registration if you add the field
            'email' => $registration->email,
            'password' => $registration->password, // Already hashed
            'registration_client_id' => $registration->client_id,
        ]);

        // Link the registration to the new user (keep the token for auditing)
        $registration->update([
            'user_id' => $user->id,
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        return $this->tokenResponse($token, UserResource::make($user));
    }

    /**
     * Log in a user.
     */
    public function login(LoginRequest $request)
    {
        if (! Auth::attempt($request->only('email', 'password'))) {
            return $this->unauthorizedResponse('Invalid credentials');
        }

        $user = Auth::user();
        $token = $user->createToken($request->ip() ?? 'api-token');

        Entry::create([
            'user_id' => $user->id,
            'client_id' => $request->header('X-Client-Key'),
            'token_id' => $token->accessToken->id,
            'ip_address' => $request->ip(),
            'device_info' => $request->userAgent(),
            'logged_in_at' => now(),
        ]);

        return $this->tokenResponse($token->plainTextToken, UserResource::make($user));
    }

    /**
     * Handle social login.
     */
    public function socialLogin(SocialLoginRequest $request)
    {
        $providerUser = $this->handleServiceCall(
            function () use ($request) {
                // userFromToken exists in Socialite but isn't in type definitions
                /** @phpstan-ignore-next-line */
                return Socialite::driver($request->provider)->userFromToken($request->access_token);
            },
            'Invalid provider token',
            401
        );

        if ($providerUser instanceof JsonResponse) {
            return $providerUser;
        }

        // Find or create the social account
        $socialAccount = SocialAccount::firstOrCreate(
            [
                'provider_name' => $request->provider,
                'provider_id' => $providerUser->getId(),
            ],
            [
                'provider_token' => $providerUser->token,
                'provider_refresh_token' => $providerUser->refreshToken,
            ]
        );

        // Find or create the user
        $user = User::firstOrCreate(
            ['email' => $providerUser->getEmail()],
            [
                'name' => $providerUser->getName(),
                'password' => Hash::make(Str::random(24)), // Create a random password
                'registration_client_id' => $request->client_id,
            ]
        );

        // Link the user and social account if it's not already
        if ($socialAccount->user_id !== $user->id) {
            $socialAccount->user_id = $user->id;
            $socialAccount->save();
        }

        $token = $user->createToken($request->ip() ?? 'api-token');

        Entry::create([
            'user_id' => $user->id,
            'client_id' => $request->header('X-Client-Key'),
            'token_id' => $token->accessToken->id,
            'ip_address' => $request->ip(),
            'device_info' => $request->userAgent(),
            'logged_in_at' => now(),
        ]);

        return $this->tokenResponse($token->plainTextToken, UserResource::make($user));
    }

    /**
     * Log out a user.
     */
    public function logout(Request $request)
    {
        $user = $request->user();
        $token = $user->currentAccessToken();

        // Mark the entry as logged out (skip for test tokens)
        // @phpstan-ignore-next-line booleanAnd.leftAlwaysTrue - defensive check for test tokens
        if ($token && property_exists($token, 'id')) {
            Entry::where('token_id', $token->id)
                ->where('user_id', $user->id)
                ->latest('logged_in_at')
                ->first()
                ?->update(['logged_out_at' => now()]);
        }

        // Delete the token if it's a real token (not a transient test token)
        // @phpstan-ignore-next-line if.alwaysTrue - defensive check for test tokens
        if ($token && method_exists($token, 'delete')) {
            $token->delete();
        }

        return $this->messageResponse('Logged out successfully');
    }

    /**
     * Get the authenticated user.
     */
    public function getUser(Request $request)
    {
        return $this->resourceResponse(UserResource::make($request->user()));
    }

    /**
     * Update the authenticated user.
     */
    public function updateUser(UpdateUserRequest $request)
    {
        $user = $request->user();
        $user->update($request->validated());

        return $this->resourceResponse(UserResource::make($user), 'User updated successfully');
    }
}
