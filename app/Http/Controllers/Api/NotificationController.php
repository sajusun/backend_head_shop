<?php 

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    use ApiResponse;
    /**
     * Get unread notification count (lightweight polling endpoint).
     */
    public function unreadCount()
    {
        try {
            $count = Notification::where('user_id', auth('api')->id())
                ->where('is_read', false)
                ->count();

            return $this->success('Successfully retrieved', ['unread_count' => $count]);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            return $this->error('Somethings Went Wrong', 500);
        }
    }

    /**
     * Display a listing of the user's notifications.
     */
    public function index()
    {
        try {
            $notifications = Notification::where('user_id', auth('api')->id())
                ->latest()
                ->paginate(20);

            return $this->success('Successfully retrieve', $notifications->items(), true, $notifications);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            return $this->error('Somethings Went Wrong', 500);
        }
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(string $id)
    {
        try {
            $notification = Notification::where('user_id', auth('api')->id())
                ->findOrFail($id);

            $notification->update(['is_read' => true]);

            return $this->success('Notification marked as read');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('Notification not found', 404);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            return $this->error('Somethings Went Wrong', 500);
        }
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead()
    {
        try {
            Notification::where('user_id', auth('api')->id())
                ->where('is_read', false)
                ->update(['is_read' => true]);

            return $this->success('All notifications marked as read');
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            return $this->error('Somethings Went Wrong', 500);
        }
    }

    /**
     * Remove a notification.
     */
    public function destroy(string $id)
    {
        try {
            $notification = Notification::where('user_id', auth('api')->id())
                ->findOrFail($id);

            $notification->delete();

            return $this->success('Notification deleted successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('Notification not found', 404);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            return $this->error('Somethings Went Wrong', 500);
        }
    }
}
