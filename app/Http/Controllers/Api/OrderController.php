<?php 

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Services\RankingService;
use App\Services\NotificationService;
use App\Jobs\SendOrderPlacedEmail;

class OrderController extends Controller
{
    use ApiResponse;

    protected $rankingService;

    public function __construct(RankingService $rankingService)
    {
        $this->rankingService = $rankingService;
    }

    /**
     * Display a listing of the user's orders.
     */
    public function index()
    {
        try {
            $orders = Order::where('user_id', auth('api')->id())
                ->with(['items.product', 'items.productVariant.color', 'items.productVariant.size'])
                ->latest()
                ->paginate(10);

            return $this->success('Successfully retrieve', $orders->items(), true, $orders);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            return $this->error('Somethings Went Wrong', 500);
        }
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
            'items.*.product_variant_id' => 'nullable|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.selected_color' => 'nullable|string',
            'items.*.selected_size' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation error', 422, $validator->errors()->toArray());
        }

        try {
            $order = DB::transaction(function () use ($request) {
                $subtotal = 0;
                $orderItems = [];

                foreach ($request->items as $itemData) {
                    $product = Product::findOrFail($itemData['product_id']);
                    $variant = null;

                    if (!empty($itemData['product_variant_id'])) {
                        $variant = ProductVariant::with(['color', 'size'])
                            ->where('product_id', $product->id)
                            ->findOrFail($itemData['product_variant_id']);
                    } elseif ($product->variants()->exists()) {
                        $variant = $product->variants()->with(['color', 'size'])->first();
                    }

                    if ($variant && $variant->stock < $itemData['quantity']) {
                        throw new \Exception("Insufficient stock for product: {$product->name}");
                    }

                    $price = $variant
                        ? ($variant->offer_price ?? $variant->price)
                        : ($product->offer_price ?? $product->price);
                    $lineTotal = $price * $itemData['quantity'];
                    $subtotal += $lineTotal;

                    $orderItems[] = [
                        'product_id' => $product->id,
                        'product_variant_id' => $variant?->id,
                        'quantity' => $itemData['quantity'],
                        'price' => $price,
                        'selected_color' => $variant?->color?->name ?? $itemData['selected_color'] ?? null,
                        'selected_size' => $variant?->size?->name ?? $itemData['selected_size'] ?? null,
                        'other_variants' => null,
                    ];

                    if ($variant) {
                        $variant->decrement('stock', $itemData['quantity']);
                    }
                }

                // Handle Coupon if provided
                $coupon = null;
                $discountAmount = 0;
                if ($request->filled('coupon_code')) {
                    $code = strtoupper(trim($request->coupon_code));
                    $foundCoupon = Coupon::where('code', $code)->first();
                    if ($foundCoupon) {
                        $validation = $foundCoupon->isValidFor($subtotal, auth('api')->id());
                        if ($validation['valid']) {
                            $coupon = $foundCoupon;
                            $discountAmount = $foundCoupon->calculateDiscount($subtotal);
                        }
                    }
                }

                $deliveryZone = $request->input('delivery_zone', 'inside');
                $shippingCost = $deliveryZone === 'outside' ? 120 : 60;
                if ($coupon && $coupon->type === 'free_shipping') {
                    $shippingCost = 0;
                }

                $tax = 0;
                $totalAmount = max(0, round($subtotal - $discountAmount + $shippingCost + $tax, 2));

                $order = Order::create([
                    'order_number' => 'ORD-' . date('Ymd') . '-' . strtoupper(Str::random(6)),
                    'user_id' => auth('api')->id(),
                    'coupon_id' => $coupon?->id,
                    'coupon_code' => $coupon?->code,
                    'discount_amount' => $discountAmount,
                    'delivery_zone' => $deliveryZone,
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

                // Track coupon usage
                if ($coupon) {
                    $coupon->increment('used_count');
                    CouponUsage::create([
                        'coupon_id' => $coupon->id,
                        'user_id' => auth('api')->id(),
                        'order_id' => $order->id,
                        'discount_amount' => $discountAmount,
                    ]);
                }

                foreach ($orderItems as $item) {
                    $item['order_id'] = $order->id;
                    OrderItem::create($item);

                    // Increment sales count
                    $product = Product::find($item['product_id']);
                    if ($product) {
                        $this->rankingService->incrementSales($product, $item['quantity']);
                    }
                }

                return $order->load(['items.product', 'items.productVariant.color', 'items.productVariant.size']);
            });

            $user = auth('api')->user();

            // In-app notification — gated by order_updates preference
            if ($user) {
                NotificationService::createForUser(
                    $user,
                    'order',
                    "Order #{$order->order_number} Confirmed! ✅",
                    'Your order has been placed successfully and is now being reviewed.',
                    '/orders',
                    ['order_id' => $order->id, 'order_number' => $order->order_number],
                    'order_updates'
                );

                // Notify Admins
                NotificationService::notifyAdmins(
                    'order',
                    "New Order Received! 🛒",
                    "A new order (#{$order->order_number}) has been placed by {$request->full_name}.",
                    '/admin/orders',
                    ['order_id' => $order->id, 'order_number' => $order->order_number]
                );
            }

            // Queue confirmation email — gated by order_updates preference
            if (!$user || NotificationService::userAllows($user, 'order_updates')) {
                SendOrderPlacedEmail::dispatch($order);
            }

            return $this->success('Order placed successfully', $order, false, null, true, 201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('Product not found', 404);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            $code = $th instanceof \Exception ? 400 : 500;
            return $this->error($th->getMessage() ?: 'Somethings Went Wrong', $code);
        }
    }

    /**
     * Display the specified order.
     */
    public function show(string $id)
    {
        try {
            $order = Order::with(['items.product', 'items.productVariant.color', 'items.productVariant.size'])
                ->where('id', $id)
                ->where(function ($q) {
                    $q->where('user_id', auth('api')->id())
                        ->orWhereNull('user_id'); // Allow viewing if guest (with specific check if needed)
                })
                ->findOrFail($id);

            return $this->success('Successfully retrieve', $order);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('Order not found', 404);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            return $this->error('Somethings Went Wrong', 500);
        }
    }
}
