<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SettingController extends Controller
{
    /**
     * Get all public settings
     */
    public function index()
    {
        $settings = Setting::all()->pluck('value', 'key');
        
        return response()->json([
            'status' => 'success',
            'data' => $settings
        ]);
    }

    /**
     * Update settings (Admin only)
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'settings' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        foreach ($request->settings as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Settings updated successfully'
        ]);
    }
    
    /**
     * Get settings by group
     */
    public function getByGroup($group)
    {
        $settings = Setting::where('group', $group)->pluck('value', 'key');
        
        return response()->json([
            'status' => 'success',
            'data' => $settings
        ]);
    }
}
