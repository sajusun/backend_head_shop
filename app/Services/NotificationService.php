<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

class NotificationService
{
    /**
     * Default notification preferences for new users.
     */
    public static function defaultPreferences(): array
    {
        return [
            'order_updates' => true,
            'promotions'    => true,
            'newsletter'    => true,
        ];
    }

    /**
     * Check if a user has a specific notification preference enabled.
     *
     * @param User   $user    The user to check
     * @param string $channel 'order_updates' | 'promotions' | 'newsletter'
     */
    public static function userAllows(User $user, string $channel): bool
    {
        $prefs = $user->notification_preferences ?? self::defaultPreferences();

        if (is_string($prefs)) {
            $prefs = json_decode($prefs, true) ?? self::defaultPreferences();
        }

        return (bool) ($prefs[$channel] ?? true);
    }

    /**
     * Create a DB notification for a single user.
     * Optionally gate by preference channel (pass null to skip gate).
     *
     * @param User        $user
     * @param string      $type    'order'|'promo'|'info'|'alert'
     * @param string      $title
     * @param string      $message
     * @param string|null $link
     * @param array|null  $data    Extra JSON metadata
     * @param string|null $channel Preference channel to check (null = always create)
     */
    public static function createForUser(
        User $user,
        string $type,
        string $title,
        string $message,
        ?string $link = null,
        ?array $data = null,
        ?string $channel = null
    ): ?Notification {
        // Gate check
        if ($channel !== null && !self::userAllows($user, $channel)) {
            return null;
        }

        return Notification::create([
            'user_id' => $user->id,
            'type'    => $type,
            'title'   => $title,
            'message' => $message,
            'link'    => $link,
            'is_read' => false,
            'data'    => $data,
        ]);
    }

    /**
     * Broadcast a promo notification + email to all users who opted in to 'promotions'.
     * Returns the count of users notified.
     *
     * @param string      $title
     * @param string      $message
     * @param string|null $link
     * @param array|null  $data      Extra metadata (product info, etc.)
     * @param int|null    $targetUserId If set, only notify this specific user
     */
    public static function broadcastPromo(
        string $title,
        string $message,
        ?string $link = null,
        ?array $data = null,
        ?int $targetUserId = null
    ): int {
        $query = User::where('role', 'user');

        if ($targetUserId) {
            $query->where('id', $targetUserId);
        }

        $users = $query->get();
        $count = 0;

        foreach ($users as $user) {
            $notification = self::createForUser(
                $user,
                'promo',
                $title,
                $message,
                $link,
                $data,
                'promotions'
            );

            if ($notification) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Get the label for an order status change notification.
     */
    public static function getOrderStatusLabel(string $status): array
    {
        return match ($status) {
            'processing' => [
                'title'   => 'Order Is Being Processed',
                'message' => 'Great news! Your order is now being processed by our team.',
            ],
            'shipped' => [
                'title'   => 'Your Order Has Shipped 🚚',
                'message' => 'Your order is on its way! It will be delivered soon.',
            ],
            'delivered' => [
                'title'   => 'Order Delivered! 📦',
                'message' => 'Your order has been delivered. Enjoy your purchase!',
            ],
            'cancelled' => [
                'title'   => 'Order Cancelled',
                'message' => 'Your order has been cancelled. Contact us if you need help.',
            ],
            default => [
                'title'   => 'Order Status Updated',
                'message' => "Your order status has been updated to: {$status}.",
            ],
        };
    }
}
