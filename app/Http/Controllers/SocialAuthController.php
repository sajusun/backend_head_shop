<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SocialAuthController extends Controller
{
    // Redirect to Google
public function redirect(Request $request)
{
    $request->validate([
        'provider' => 'required|in:google,facebook,github,twitter,linkedin',
    ]);

    $provider = $request->provider;
    return Socialite::driver($provider)->redirect();
}

// Handle callback
public function callback(Request $request)
{
    $request->validate([
        'provider' => 'required|in:google,facebook,github,twitter,linkedin',
        'code' => 'required',
    ]);

    $provider = $request->provider;

    try {
        $socialUser = Socialite::driver($provider)->user();
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Social login failed',
        ], 400);
    }

    // Find or create user
    $user = User::firstOrCreate(
        ['email' => $socialUser->getEmail()],
        [
            'name' => $socialUser->getName()  ?? $socialUser->getNickname() ?? explode('@', $socialUser->getEmail())[0],
            'provider' => $provider,
            'provider_id' => $socialUser->getId(),
            'avatar' => $socialUser->getAvatar(),   
            'email_verified_at' => now(),
            'password' => bcrypt(uniqid())
        ]
    );

    $token = $user->createToken('social-token')->accessToken;

    return response()->json([
        'success' => true,
        'token' => $token,
        'user' => $user,
        'message' => 'Login successful',
    ]);
}
}
