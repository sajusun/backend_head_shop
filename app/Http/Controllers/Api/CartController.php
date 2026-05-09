<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\ProductResource;

class CartController extends Controller
{
    use ApiResponse;

    /**
     * Get the authenticated user's cart.
     */
    public function index()
    {
        try {
            $cartItems = Cart::where('user_id', auth('api')->id())
                ->with(['product.primaryImage', 'product.variants.color', 'product.variants.size', 'variant.color', 'variant.size'])
                ->get();

            $formattedItems = $cartItems->map(function ($item) {
                // We use the same format as the frontend's cart payload
                $product = $item->product;
                $variant = $item->variant;

                // Load necessary data to match ProductResource format
                $product->loadCount('approvedReviews');
                $product->loadAvg('approvedReviews', 'rating');

                $productData = ProductResource::make($product)->resolve(request());

                return array_merge($productData, [
                    'cart_id' => $item->id,
                    'product_variant_id' => $item->product_variant_id,
                    'quantity' => $item->quantity,
                    'selectedColor' => $item->selected_color,
                    'selectedSize' => $item->selected_size,
                ]);
            });

            return $this->success('Successfully retrieve', $formattedItems);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            return $this->error('Somethings Went Wrong', 500);
        }
    }

    /**
     * Add or update an item in the cart.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'product_variant_id' => 'nullable|exists:product_variants,id',
            'quantity' => 'required|integer|min:1',
            'selectedColor' => 'nullable|string',
            'selectedSize' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation error', 422, $validator->errors()->toArray());
        }

        try {
            $user_id = auth('api')->id();
            
            // Check if item already exists in cart
            $cartItem = Cart::where('user_id', $user_id)
                ->where('product_id', $request->product_id)
                ->where('product_variant_id', $request->product_variant_id)
                ->where('selected_color', $request->selectedColor)
                ->where('selected_size', $request->selectedSize)
                ->first();

            if ($cartItem) {
                $cartItem->increment('quantity', $request->quantity);
            } else {
                $cartItem = Cart::create([
                    'user_id' => $user_id,
                    'product_id' => $request->product_id,
                    'product_variant_id' => $request->product_variant_id,
                    'quantity' => $request->quantity,
                    'selected_color' => $request->selectedColor,
                    'selected_size' => $request->selectedSize,
                ]);
            }

            return $this->success('Item added to cart', $cartItem);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            return $this->error('Somethings Went Wrong', 500);
        }
    }

    /**
     * Sync multiple items (usually on login).
     */
    public function sync(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array',
            'items.*.id' => 'required|exists:products,id',
            'items.*.product_variant_id' => 'nullable|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.selectedColor' => 'nullable|string',
            'items.*.selectedSize' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation error', 422, $validator->errors()->toArray());
        }

        try {
            $user_id = auth('api')->id();

            foreach ($request->items as $item) {
                Cart::updateOrCreate(
                    [
                        'user_id' => $user_id,
                        'product_id' => $item['id'],
                        'product_variant_id' => $item['product_variant_id'] ?? null,
                        'selected_color' => $item['selectedColor'] ?? null,
                        'selected_size' => $item['selectedSize'] ?? null,
                    ],
                    [
                        'quantity' => $item['quantity']
                    ]
                );
            }

            return $this->index(); // Return updated cart
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            return $this->error('Somethings Went Wrong', 500);
        }
    }

    /**
     * Update quantity of a cart item.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation error', 422, $validator->errors()->toArray());
        }

        try {
            $cartItem = Cart::where('user_id', auth('api')->id())->findOrFail($id);
            $cartItem->update(['quantity' => $request->quantity]);

            return $this->success('Quantity updated', $cartItem);
        } catch (\Throwable $th) {
            return $this->error('Item not found', 404);
        }
    }

    /**
     * Remove an item from the cart.
     */
    public function destroy($id)
    {
        try {
            $cartItem = Cart::where('user_id', auth('api')->id())->findOrFail($id);
            $cartItem->delete();

            return $this->success('Item removed');
        } catch (\Throwable $th) {
            return $this->error('Item not found', 404);
        }
    }

    /**
     * Clear the entire cart.
     */
    public function clear()
    {
        try {
            Cart::where('user_id', auth('api')->id())->delete();
            return $this->success('Cart cleared');
        } catch (\Throwable $th) {
            return $this->error('Somethings Went Wrong', 500);
        }
    }
}
