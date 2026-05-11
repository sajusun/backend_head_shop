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

class AdminAuthController extends Controller
{
    use ApiResponse;

    /**
     * Get a JWT via given credentials for Admin only.
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

        // Attempt login with credentials
        if (!$token = auth('api')->attempt($validator->validated())) {
            return $this->error('Invalid credentials', 401);
        }

        $user = auth('api')->user();

        // STRICT CHECK: Only allow admin role to access this route
        if ($user->role !== 'admin') {
            auth('api')->logout();
            return $this->error('Access Denied: You do not have administrative privileges.', 403);
        }

        // Check if 2FA is enabled
        if ($user->hasTwoFactorEnabled()) {
            return $this->success('2FA challenge required', [
                'requires_2fa' => true,
                'email' => $user->email,
            ]);
        }

        return $this->createNewToken($token);
    }

    /**
     * Verify 2FA code during login for admin.
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

        // Role check again for security
        if ($user->role !== 'admin') {
            auth('api')->logout();
            return $this->error('Access Denied', 403);
        }

        $google2fa = new Google2FA();

        if ($google2fa->verifyKey($user->two_factor_secret, $request->code)) {
            return $this->createNewToken($token);
        }

        return $this->error('Invalid 2FA code', 422);
    }

    /**
     * Log the admin out.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        try {
            auth('api')->logout();
            return $this->success('Admin successfully signed out');
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            return $this->error('Something Went Wrong', 500);
        }
    }

    /**
     * Refresh admin token.
     */
    public function refresh()
    {
        try {
            return $this->createNewToken(auth('api')->refresh());
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            return $this->error('Something Went Wrong', 500);
        }
    }

    /**
     * Get the authenticated admin profile.
     */
    public function profile()
    {
        try {
            $user = auth('api')->user();
            if ($user && $user->role === 'admin') {
                $user->refresh();
                return $this->success('Successfully retrieved admin profile', $user);
            }
            return $this->error('Access Denied', 403);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            return $this->error('Something Went Wrong', 500);
        }
    }

    /**
     * Get 2FA QR Code and Secret for Admin.
     */
    public function get2FAQrCode(Request $request)
    {
        $user = auth('api')->user();
        
        // Ensure only admin can access this
        if ($user->role !== 'admin') {
            return $this->error('Unauthorized', 403);
        }

        $google2fa = new Google2FA();

        // Generate secret if not exists or if not confirmed
        if (!$user->two_factor_secret || !$user->two_factor_confirmed_at) {
            $user->two_factor_secret = $google2fa->generateSecretKey();
            $user->save();
        }

        $qrCodeUrl = $google2fa->getQRCodeUrl(
            config('app.name') . ' Admin',
            $user->email,
            $user->two_factor_secret
        );

        // Generate QR Code Image as SVG Data URL
        // Using standard SVG rendering to avoid complex dependencies
        $renderer = new \BaconQrCode\Renderer\ImageRenderer(
            new \BaconQrCode\Renderer\RendererStyle\RendererStyle(200),
            new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
        );
        $writer = new \BaconQrCode\Writer($renderer);
        $qrCodeImage = 'data:image/svg+xml;base64,' . base64_encode($writer->writeString($qrCodeUrl));

        return $this->success('Admin 2FA QR Code generated', [
            'qr_code' => $qrCodeImage,
            'secret' => $user->two_factor_secret,
        ]);
    }

    /**
     * Confirm and enable 2FA for Admin.
     */
    public function confirm2FA(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $user = auth('api')->user();
        
        if ($user->role !== 'admin') {
            return $this->error('Unauthorized', 403);
        }

        $google2fa = new Google2FA();
        $verified = $google2fa->verifyKey($user->two_factor_secret, $request->code);

        if ($verified) {
            $user->two_factor_confirmed_at = now();
            
            // Generate recovery codes
            $recoveryCodes = collect(range(1, 8))->map(function () {
                return \Illuminate\Support\Str::random(10) . '-' . \Illuminate\Support\Str::random(10);
            })->toArray();
            
            $user->two_factor_recovery_codes = $recoveryCodes;
            $user->save();

            return $this->success('Admin 2FA enabled successfully', [
                'recovery_codes' => $recoveryCodes
            ]);
        }

        return $this->error('Invalid verification code', 422);
    }

    /**
     * Disable 2FA for Admin.
     */
    public function disable2FA(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
        ]);

        $user = auth('api')->user();

        if ($user->role !== 'admin') {
            return $this->error('Unauthorized', 403);
        }

        if (!Hash::check($request->current_password, $user->password)) {
            return $this->error('Invalid password', 422);
        }

        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;
        $user->two_factor_confirmed_at = null;
        $user->save();

        return $this->success('Admin 2FA disabled successfully');
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

        return $this->success('Login successful', $payload);
    }
}
