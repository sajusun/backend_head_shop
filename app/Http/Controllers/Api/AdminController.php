<?php 

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Blog;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function getDashboardStats()
    {
        $stats = [
            'total_users' => User::count(),
            'total_products' => Product::count(),
            'total_orders' => Order::count(),
            'total_revenue' => Order::where('payment_status', 'paid')->sum('total_amount'),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'published_blogs' => Blog::where('status', 'published')->count(),
            'recent_orders' => Order::with('user')->latest()->take(5)->get(),
        ];

        return response()->json($stats);
    }

    /**
     * Get all orders with pagination and filtering.
     */
    public function getAllOrders(Request $request)
    {
        $query = Order::with(['user', 'items.product']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->latest()->paginate($request->query('per_page', 20));
        return response()->json($orders);
    }

    /**
     * Update order status.
     */
    public function updateOrderStatus(Request $request, string $id)
    {
        $order = Order::findOrFail($id);
        
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled',
            'payment_status' => 'in:pending,paid,failed,refunded',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $order->update($request->all());

        return response()->json(['message' => 'Order updated successfully', 'order' => $order]);
    }

    /**
     * Get all users.
     */
    public function getAllUsers(Request $request)
    {
        $users = User::latest()->paginate($request->query('per_page', 20));
        return response()->json($users);
    }
}
