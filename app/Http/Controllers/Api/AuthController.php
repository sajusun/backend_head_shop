<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Storage;
use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Str;
use App\Services\NotificationService;
use App\Jobs\SendWelcomeEmail;

class AuthController extends Controller
{
    use ApiResponse;

    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        // $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    /**
     * Register a User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|between:2,100',
            'email' => 'required|string|email|max:100|unique:users',
            'password' => 'required|string|confirmed|min:6',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation error', 422, $validator->errors()->toArray());
        }

        try {
            $user = User::create([
                'name'                     => $request->name,
                'email'                    => $request->email,
                'password'                 => Hash::make($request->password),
                'role'                     => 'user',
                'notification_preferences' => NotificationService::defaultPreferences(),
            ]);

            // Welcome in-app notification (no gate — always create)
            NotificationService::createForUser(
                $user,
                'info',
                'Welcome to HERD SHOP! 🎉',
                'Your account is all set. Explore our premium collection and enjoy exclusive deals.',
                '/products'
            );

            // Queue welcome email (no gate)
            SendWelcomeEmail::dispatch($user);

            $token = auth('api')->login($user);

            return $this->success('User successfully registered', [
                'access_token' => $token,
                'token_type'   => 'bearer',
                'expires_in'   => auth('api')->factory()->getTTL() * 60,
                'user'         => $user,
            ], false, null, true, 201);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            return $this->error('Somethings Went Wrong', 500);
        }
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation error', 422, $validator->errors()->toArray());
        }

        if (!$token = auth('api')->attempt($validator->validated())) {
            return $this->error('Unauthorized', 401);
        }

        $user = auth('api')->user();

        // STRICT CHECK: Regular user login route should NOT allow admins
        if ($user->role === 'admin') {
            auth('api')->logout();
            return $this->error('Please use the admin login portal.', 403);
        }

        // Check if 2FA is enabled
        if ($user->hasTwoFactorEnabled()) {
            // We don't return the token yet. 
            // The frontend needs to provide a 2FA code first.
            return $this->success('2FA challenge required', [
                'requires_2fa' => true,
                'email' => $user->email,
            ]);
        }

        return $this->createNewToken($token);
    }

    /**
     * Verify 2FA code during login and issue token.
     */
    public function loginWith2FA(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
            'code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation error', 422, $validator->errors()->toArray());
        }

        if (!$token = auth('api')->attempt($request->only('email', 'password'))) {
            return $this->error('Unauthorized', 401);
        }

        $user = auth('api')->user();

        // Role check for isolation
        if ($user->role === 'admin') {
            auth('api')->logout();
            return $this->error('Please use the admin login portal.', 403);
        }

        $google2fa = new Google2FA();

        if ($google2fa->verifyKey($user->two_factor_secret, $request->code)) {
            return $this->createNewToken($token);
        }

        return $this->error('Invalid 2FA code', 422);
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        try {
            auth('api')->logout();
            return $this->success('User successfully signed out');
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            return $this->error('Somethings Went Wrong', 500);
        }
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        try {
            return $this->createNewToken(auth('api')->refresh());
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            return $this->error('Somethings Went Wrong', 500);
        }
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function userProfile()
    {
        try {
            $user = auth('api')->user();
            if ($user) {
                $user->refresh();
            }

            return $this->success('Successfully retrieve', $user);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            return $this->error('Somethings Went Wrong', 500);
        }
    }

    /**
     * Update the authenticated user's profile.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfile(Request $request)
    {
        $user = auth('api')->user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|between:2,100',
            'phone' => 'nullable|string|max:20',
            'bio' => 'nullable|string|max:1000',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', // 2MB Max
        ]);

        if ($validator->fails()) {
            return $this->error('Validation error', 422, $validator->errors()->toArray());
        }

        try {
            $data = $request->only('name', 'phone', 'bio');

            if ($request->hasFile('avatar')) {
                // Delete old avatar if it exists and is not a default/external URL
                if ($user->avatar && !str_starts_with($user->avatar, 'http')) {
                    Storage::disk('public')->delete(str_replace('/storage/', '', $user->avatar));
                }

                $path = $request->file('avatar')->store('avatars', 'public');
                $data['avatar'] = Storage::url($path);
            }

            $user->update($data);
            $user->refresh();
            
            return $this->success('Profile updated successfully', $user);
        } catch (\Throwable $th) {
            Log::error('Profile Update Error: ' . $th->getMessage());
            return $this->error('Failed to update profile', 500);
        }
    }

    /**
     * Change the authenticated user's password.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation error', 422, $validator->errors()->toArray());
        }

        $user = auth('api')->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return $this->error('The provided current password does not match your record.', 422, [
                'current_password' => ['The provided current password does not match your record.']
            ]);
        }

        try {
            $user->update([
                'password' => Hash::make($request->password)
            ]);

            return $this->success('Password changed successfully');
        } catch (\Throwable $th) {
            Log::error('Password Change Error: ' . $th->getMessage());
            return $this->error('Failed to change password', 500);
        }
    }

    /**
     * Get 2FA QR Code and Secret.
     */
    public function get2FAQrCode(Request $request)
    {
        $user = auth('api')->user();
        $google2fa = new Google2FA();

        // Generate secret if not exists or if not confirmed
        if (!$user->two_factor_secret || !$user->two_factor_confirmed_at) {
            $user->two_factor_secret = $google2fa->generateSecretKey();
            $user->save();
        }

        $qrCodeUrl = $google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $user->two_factor_secret
        );

        // Generate QR Code Image as SVG Data URL
        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd()
        );
        $writer = new Writer($renderer);
        $qrCodeImage = 'data:image/svg+xml;base64,' . base64_encode($writer->writeString($qrCodeUrl));

        return $this->success('2FA QR Code generated', [
            'qr_code' => $qrCodeImage,
            'secret' => $user->two_factor_secret,
        ]);
    }

    /**
     * Confirm and enable 2FA.
     */
    public function confirm2FA(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $user = auth('api')->user();
        $google2fa = new Google2FA();

        $verified = $google2fa->verifyKey($user->two_factor_secret, $request->code);

        if ($verified) {
            $user->two_factor_confirmed_at = now();
            
            // Generate recovery codes
            $recoveryCodes = collect(range(1, 8))->map(function () {
                return Str::random(10) . '-' . Str::random(10);
            })->toArray();
            
            $user->two_factor_recovery_codes = $recoveryCodes;
            $user->save();

            return $this->success('2FA enabled successfully', [
                'recovery_codes' => $recoveryCodes
            ]);
        }

        return $this->error('Invalid verification code', 422);
    }

    /**
     * Disable 2FA.
     */
    public function disable2FA(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
        ]);

        $user = auth('api')->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return $this->error('Invalid password', 422);
        }

        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;
        $user->two_factor_confirmed_at = null;
        $user->save();

        return $this->success('2FA disabled successfully');
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function createNewToken($token)
    {
        $payload = [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'user' => auth('api')->user()
        ];

        return $this->success('Successfully retrieve', $payload);
    }
}
