<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SocialAuthController extends Controller
{
    use ApiResponse;

    /**
     * Redirect the user to the provider's authentication page.
     *
     * @param string $provider
     * @return \Illuminate\Http\JsonResponse
     */
    public function redirect(string $provider)
    {
        $validProviders = ['google', 'facebook', 'github', 'twitter', 'linkedin'];

        if (!in_array($provider, $validProviders)) {
            return $this->error('Invalid provider', 400);
        }

        try {
            $url = Socialite::driver($provider)->stateless()->redirect()->getTargetUrl();
            return $this->success('Redirect URL generated', ['url' => $url]);
        } catch (\Exception $e) {
            Log::error('Social redirect error: ' . $e->getMessage());
            return $this->error('Could not generate redirect URL', 500);
        }
    }

    /**
     * Obtain the user information from the provider.
     *
     * @param string $provider
     * @return \Illuminate\Http\JsonResponse
     */
    public function callback(string $provider)
    {
        try {
            $socialUser = Socialite::driver($provider)->stateless()->user();
        } catch (\Exception $e) {
            Log::error('Social callback error: ' . $e->getMessage());
            return $this->error('Social login failed', 400);
        }

        // Find or create user
        $user = User::where('provider', $provider)->where('provider_id', $socialUser->getId())->first();

        if (!$user) {
            // Check if user with same email exists
            $user = User::where('email', $socialUser->getEmail())->first();

            if ($user) {
                // Update existing user with provider details
                $user->update([
                    'provider' => $provider,
                    'provider_id' => $socialUser->getId(),
                ]);
            } else {
                // Create new user
                $user = User::create([
                    'name' => $socialUser->getName() ?? $socialUser->getNickname() ?? explode('@', $socialUser->getEmail())[0],
                    'email' => $socialUser->getEmail(),
                    'provider' => $provider,
                    'provider_id' => $socialUser->getId(),
                    'avatar' => $socialUser->getAvatar(),
                    'email_verified_at' => now(),
                    'password' => Hash::make(Str::random(24)), // Random password for social users
                ]);
            }
        }

        // Generate JWT token
        $token = auth('api')->login($user);

        return $this->success('Login successful', [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'user' => $user,
        ]);
    }
}
