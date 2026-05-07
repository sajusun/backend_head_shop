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
use Illuminate\Support\Facades\Validator;

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
            $query = Order::with(['user', 'items.product']);

            if ($request->filled('status')) {
                $query->where('status', $request->status);
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

            return $this->success('Order updated successfully', $order);
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
            $users = User::latest()->paginate($request->query('per_page', 20));
            return $this->success('Successfully retrieve', $users->items(), true, $users);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            return $this->error('Somethings Went Wrong', 500);
        }
    }
}
