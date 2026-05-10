<?php 

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Blog;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\Color;
use App\Models\Size;
use App\Models\Amenity;
use App\Models\Category;
use App\Services\NotificationService;
use App\Jobs\SendOrderStatusEmail;
use App\Jobs\SendProductPromotionEmail;

class AdminController extends Controller
{
    use ApiResponse;

    public function getDashboardStats()
    {
        try {
            $stats = [
                'total_users' => User::count(),
                'total_products' => Product::count(),
                'total_orders' => Order::count(),
                'total_revenue' => Order::where('payment_status', 'paid')->sum('total_amount'),
                'pending_orders' => Order::where('status', 'pending')->count(),
                'published_blogs' => Blog::where('status', 'published')->count(),
                'recent_orders' => Order::with('user')->latest()->take(5)->get(),
                'chart_data' => Order::selectRaw('DATE(created_at) as date, SUM(total_amount) as total')
                    ->where('payment_status', 'paid')
                    ->where('created_at', '>=', now()->subDays(7))
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get()
                    ->map(fn($item) => [
                        'name' => \Carbon\Carbon::parse($item->date)->format('D'),
                        'sales' => (float)$item->total,
                    ]),
                'product_stats' => [
                    'active' => Product::where('status', 'published')->count(),
                    'draft' => Product::where('status', 'draft')->count(),
                ]
            ];

            return $this->success('Successfully retrieve', $stats);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            return $this->error('Somethings Went Wrong', 500);
        }
    }

    /**
     * Get all orders with pagination and filtering.
     */
    public function getAllOrders(Request $request)
    {
        try {
            $query = Order::with(['user', 'items.product.primaryImage', 'items.productVariant.color', 'items.productVariant.size']);

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('payment_status')) {
                $query->where('payment_status', $request->payment_status);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('order_number', 'like', "%{$search}%")
                        ->orWhere('full_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            }

            $orders = $query->latest()->paginate($request->query('per_page', 20));
            return $this->success('Successfully retrieve', $orders->items(), true, $orders);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            return $this->error('Somethings Went Wrong', 500);
        }
    }

    /**
     * Update order status.
     */
    public function updateOrderStatus(Request $request, string $id)
    {
        try {
            $order = Order::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'status' => 'required|in:pending,processing,shipped,delivered,cancelled',
                'payment_status' => 'in:pending,paid,failed,refunded',
            ]);

            if ($validator->fails()) {
                return $this->error('Validation error', 422, $validator->errors()->toArray());
            }

            $order->update($validator->validated());

            // Fire notification + email to order owner when status changes
            if ($order->user_id && isset($validator->validated()['status'])) {
                $newStatus = $validator->validated()['status'];
                $orderUser = $order->user;

                if ($orderUser) {
                    $label = NotificationService::getOrderStatusLabel($newStatus);

                    NotificationService::createForUser(
                        $orderUser,
                        'order',
                        $label['title'],
                        $label['message'],
                        '/orders',
                        ['order_id' => $order->id, 'order_number' => $order->order_number],
                        'order_updates'
                    );

                    if (NotificationService::userAllows($orderUser, 'order_updates')) {
                        SendOrderStatusEmail::dispatch($order, $newStatus);
                    }
                }
            }

            return $this->success('Order updated successfully', $order);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('Order not found', 404);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            return $this->error('Somethings Went Wrong', 500);
        }
    }

    /**
     * Get single order details.
     */
    public function getOrderDetails(string $id)
    {
        try {
            $order = Order::with(['user', 'items.product.primaryImage', 'items.productVariant.color', 'items.productVariant.size'])->findOrFail($id);
            return $this->success('Successfully retrieve', $order);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('Order not found', 404);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            return $this->error('Somethings Went Wrong', 500);
        }
    }

    /**
     * Get all users.
     */
    public function getAllUsers(Request $request)
    {
        try {
            $query = User::query();

            if ($request->filled('search')) {
                $search = $request->query('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            }

            if ($request->filled('role')) {
                $query->where('role', $request->query('role'));
            }

            $users = $query->latest()->paginate($request->query('per_page', 20));
            return $this->success('Successfully retrieve', $users->items(), true, $users);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            return $this->error('Somethings Went Wrong', 500);
        }
    }

    public function storeUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:100|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,user',
            'phone' => 'nullable|string|max:30',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation error', 422, $validator->errors()->toArray());
        }

        try {
            $data = $validator->validated();
            $data['password'] = Hash::make($data['password']);
            $data['email_verified_at'] = now();

            $user = User::create($data);

            return $this->success('User created successfully', $user, false, null, true, 201);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            return $this->error('Somethings Went Wrong', 500);
        }
    }

    public function updateUser(Request $request, string $id)
    {
        try {
            $user = User::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:100',
                'email' => 'sometimes|required|email|max:100|unique:users,email,' . $id,
                'password' => 'nullable|string|min:6',
                'role' => 'sometimes|required|in:admin,user',
                'phone' => 'nullable|string|max:30',
            ]);

            if ($validator->fails()) {
                return $this->error('Validation error', 422, $validator->errors()->toArray());
            }

            $data = $validator->validated();
            if (empty($data['password'])) {
                unset($data['password']);
            } else {
                $data['password'] = Hash::make($data['password']);
            }

            $user->update($data);

            return $this->success('User updated successfully', $user);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('User not found', 404);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            return $this->error('Somethings Went Wrong', 500);
        }
    }

    public function deleteUser(string $id)
    {
        try {
            $user = User::findOrFail($id);

            if ($user->id === auth('api')->id()) {
                return $this->error('You cannot delete your own admin account', 422);
            }

            $user->delete();

            return $this->success('User deleted successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('User not found', 404);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            return $this->error('Somethings Went Wrong', 500);
        }
    }
    /**
     * Get all blogs with pagination and filtering.
     */
    public function getAllBlogs(Request $request)
    {
        try {
            $query = Blog::with(['author', 'category']);

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('category')) {
                $query->where('category_id', $request->category);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('content', 'like', "%{$search}%");
                });
            }

            $blogs = $query->latest()->paginate($request->query('per_page', 20));
            return $this->success('Successfully retrieve', $blogs->items(), true, $blogs);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            return $this->error('Somethings Went Wrong', 500);
        }
    }

    public function getAttributes()
    {
        try {
            $data = [
                'colors'     => Color::where('status', 'active')->get(),
                'sizes'      => Size::where('status', 'active')->get(),
                'amenities'  => Amenity::where('status', 'active')->get(),
                'categories' => Category::where('status', 'active')->where('type', 'product')->get(),
            ];
            return $this->success('Successfully retrieve', $data);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            return $this->error('Somethings Went Wrong', 500);
        }
    }

    /**
     * Admin: Promote a product — send notification + email to eligible users.
     */
    public function promoteProduct(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id'   => 'required|exists:products,id',
            'message'      => 'required|string|max:500',
            'target'       => 'in:all,opted_in', // 'opted_in' = only users with promotions=true
        ]);

        if ($validator->fails()) {
            return $this->error('Validation error', 422, $validator->errors()->toArray());
        }

        try {
            $product = Product::with(['primaryImage', 'category'])->findOrFail($request->product_id);
            $promoMessage = $request->message;
            $targetOnly = $request->input('target', 'opted_in') === 'opted_in';

            $users = User::where('role', 'user')->get();
            $notifiedCount = 0;
            $emailCount    = 0;

            foreach ($users as $user) {
                $allowed = NotificationService::userAllows($user, 'promotions');

                if (!$allowed && $targetOnly) {
                    continue;
                }

                // Create in-app notification
                $notification = NotificationService::createForUser(
                    $user,
                    'promo',
                    "🔥 Special Offer: {$product->name}",
                    $promoMessage,
                    "/products/{$product->slug}",
                    [
                        'product_id'    => $product->id,
                        'product_slug'  => $product->slug,
                        'product_image' => $product->primaryImage?->image_url,
                    ],
                    $targetOnly ? 'promotions' : null
                );

                if ($notification) {
                    $notifiedCount++;
                }

                // Queue promo email
                if ($allowed) {
                    SendProductPromotionEmail::dispatch($user, $product, $promoMessage);
                    $emailCount++;
                }
            }

            return $this->success(
                "Promotion sent! {$notifiedCount} notifications, {$emailCount} emails queued.",
                ['notified' => $notifiedCount, 'emails_queued' => $emailCount]
            );
        } catch (\Throwable $th) {
            Log::error('promoteProduct error: ' . $th->getMessage());
            return $this->error('Somethings Went Wrong', 500);
        }
    }

    /**
     * Admin: Broadcast a custom notification to all users or a specific user.
     */
    public function broadcastNotification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title'       => 'required|string|max:255',
            'message'     => 'required|string|max:1000',
            'link'        => 'nullable|string|max:500',
            'target_user' => 'nullable|exists:users,id', // null = broadcast to all
            'channel'     => 'in:promotions,info,order_updates', // default: promotions
        ]);

        if ($validator->fails()) {
            return $this->error('Validation error', 422, $validator->errors()->toArray());
        }

        try {
            $channel = $request->input('channel', 'promotions');
            $type    = $channel === 'order_updates' ? 'order' : ($channel === 'info' ? 'info' : 'promo');

            if ($request->filled('target_user')) {
                $user  = User::findOrFail($request->target_user);
                $count = 0;
                $notification = NotificationService::createForUser(
                    $user, $type,
                    $request->title, $request->message,
                    $request->link, null, $channel
                );
                if ($notification) $count = 1;
            } else {
                $count = NotificationService::broadcastPromo(
                    $request->title,
                    $request->message,
                    $request->link,
                    null
                );
            }

            return $this->success("Broadcast sent to {$count} user(s).", ['count' => $count]);
        } catch (\Throwable $th) {
            Log::error('broadcastNotification error: ' . $th->getMessage());
            return $this->error('Somethings Went Wrong', 500);
        }
    }
}
