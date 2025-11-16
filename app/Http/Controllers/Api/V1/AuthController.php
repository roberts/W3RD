<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Requests\Api\V1\Auth\SocialLoginRequest;
use App\Http\Requests\Api\V1\Auth\UpdateUserRequest;
use App\Http\Requests\Api\V1\Auth\VerifyRequest;
use App\Models\Auth\Entry;
use App\Models\Auth\Registration;
use App\Models\Auth\SocialAccount;
use App\Models\Auth\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
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

        return response()->json([
            'message' => 'Registration successful. Please check your email to verify your account.',
        ], 201);
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

        // Link the registration to the new user
        $registration->update([
            'user_id' => $user->id,
            'verification_token' => null, // Token is used, nullify it
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }

    /**
     * Log in a user.
     */
    public function login(LoginRequest $request)
    {
        if (! Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Invalid credentials'], 401);
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

        return response()->json([
            'token' => $token->plainTextToken,
            'user' => $user,
        ]);
    }

    /**
     * Handle social login.
     */
    public function socialLogin(SocialLoginRequest $request)
    {
        try {
            // userFromToken exists in Socialite but isn't in type definitions
            /** @phpstan-ignore-next-line */
            $providerUser = Socialite::driver($request->provider)->userFromToken($request->access_token);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Invalid provider token.'], 401);
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

        return response()->json([
            'token' => $token->plainTextToken,
            'user' => $user,
        ]);
    }

    /**
     * Log out the current user.
     */
    public function logout(Request $request)
    {
        $user = $request->user();
        $token = $user->currentAccessToken();

        // Mark the entry as logged out
        Entry::where('token_id', $token->id)->where('user_id', $user->id)->latest('logged_in_at')->first()?->update([
            'logged_out_at' => now(),
        ]);

        $token->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * Get the authenticated user.
     */
    public function getUser(Request $request)
    {
        return response()->json($request->user());
    }

    /**
     * Update the authenticated user.
     */
    public function updateUser(UpdateUserRequest $request)
    {
        $user = $request->user();
        $user->update($request->validated());

        return response()->json($user);
    }
}
