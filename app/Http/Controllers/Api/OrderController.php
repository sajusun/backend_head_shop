<?php 

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    /**
     * Display a listing of the user's orders.
     */
    public function index()
    {
        $orders = Order::where('user_id', auth('api')->id())
            ->with(['items.product'])
            ->latest()
            ->paginate(10);

        return response()->json($orders);
    }

    /**
     * Store a newly created order (Checkout).
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'required|string',
            'city' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'notes' => 'nullable|string',
            'payment_method' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.selected_color' => 'nullable|string',
            'items.*.selected_size' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        return DB::transaction(function() use ($request) {
            $subtotal = 0;
            $orderItems = [];

            foreach ($request->items as $itemData) {
                $product = Product::findOrFail($itemData['product_id']);
                
                // Stock Validation
                if ($product->stock < $itemData['quantity']) {
                    throw new \Exception("Insufficient stock for product: {$product->name}");
                }

                $price = $product->offer_price ?? $product->price;
                $lineTotal = $price * $itemData['quantity'];
                $subtotal += $lineTotal;

                $orderItems[] = [
                    'product_id' => $product->id,
                    'quantity' => $itemData['quantity'],
                    'price' => $price,
                    'selected_color' => $itemData['selected_color'] ?? null,
                    'selected_size' => $itemData['selected_size'] ?? null,
                    'other_variants' => null,
                ];

                // Update Stock
                $product->decrement('stock', $itemData['quantity']);
            }

            $shippingCost = 0; // Can be dynamic based on city/weight
            $tax = 0; // Can be dynamic
            $totalAmount = $subtotal + $shippingCost + $tax;

            $order = Order::create([
                'order_number' => 'ORD-' . date('Ymd') . '-' . strtoupper(Str::random(6)),
                'user_id' => auth('api')->id(),
                'total_amount' => $totalAmount,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'shipping_cost' => $shippingCost,
                'status' => 'pending',
                'payment_status' => 'pending',
                'payment_method' => $request->payment_method,
                'full_name' => $request->full_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'city' => $request->city,
                'postal_code' => $request->postal_code,
                'notes' => $request->notes,
            ]);

            foreach ($orderItems as $item) {
                $item['order_id'] = $order->id;
                OrderItem::create($item);
            }

            return response()->json([
                'message' => 'Order placed successfully',
                'order' => $order->load('items.product')
            ], 201);
        });
    }

    /**
     * Display the specified order.
     */
    public function show(string $id)
    {
        $order = Order::with(['items.product'])
            ->where('id', $id)
            ->where(function($q) {
                $q->where('user_id', auth('api')->id())
                  ->orWhereNull('user_id'); // Allow viewing if guest (with specific check if needed)
            })
            ->findOrFail($id);

        return response()->json($order);
    }
}
