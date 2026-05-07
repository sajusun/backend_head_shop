<?php 

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Display a listing of the user's notifications.
     */
    public function index()
    {
        $notifications = Notification::where('user_id', auth('api')->id())
            ->latest()
            ->paginate(20);

        return response()->json($notifications);
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(string $id)
    {
        $notification = Notification::where('user_id', auth('api')->id())
            ->findOrFail($id);

        $notification->update(['is_read' => true]);

        return response()->json(['message' => 'Notification marked as read']);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead()
    {
        Notification::where('user_id', auth('api')->id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['message' => 'All notifications marked as read']);
    }

    /**
     * Remove a notification.
     */
    public function destroy(string $id)
    {
        $notification = Notification::where('user_id', auth('api')->id())
            ->findOrFail($id);

        $notification->delete();

        return response()->json(['message' => 'Notification deleted successfully']);
    }
}
