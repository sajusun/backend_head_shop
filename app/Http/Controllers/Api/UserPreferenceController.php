<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UserPreferenceController extends Controller
{
    use ApiResponse;

    /**
     * Get current user's notification preferences.
     */
    public function show()
    {
        try {
            $user  = auth('api')->user();
            $prefs = $user->notification_preferences ?? NotificationService::defaultPreferences();

            if (is_string($prefs)) {
                $prefs = json_decode($prefs, true) ?? NotificationService::defaultPreferences();
            }

            return $this->success('Successfully retrieved', $prefs);
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return $this->error('Somethings Went Wrong', 500);
        }
    }

    /**
     * Update the user's notification preferences.
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_updates' => 'required|boolean',
            'promotions'    => 'required|boolean',
            'newsletter'    => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation error', 422, $validator->errors()->toArray());
        }

        try {
            $user = auth('api')->user();
            $user->update([
                'notification_preferences' => $validator->validated(),
            ]);
            $user->refresh();

            return $this->success('Notification preferences updated', $user->notification_preferences);
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return $this->error('Somethings Went Wrong', 500);
        }
    }
}
