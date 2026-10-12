<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CouponController extends Controller
{
    /**
     * List active coupons for public promotion.
     */
    public function publicCoupons()
    {
        $now = now();
        $coupons = Coupon::where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $now);
            })
            ->select(['id', 'code', 'title', 'description', 'type', 'value', 'min_order_amount', 'end_date'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $coupons,
        ]);
    }

    /**
     * Validate and apply coupon code to cart.
     */
    public function apply(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string',
            'subtotal' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide a coupon code and cart subtotal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $code = strtoupper(trim($request->code));
        $subtotal = (float) $request->subtotal;
        $userId = $request->user()?->id;

        $coupon = Coupon::where('code', $code)->first();

        if (!$coupon) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid coupon code',
            ], 404);
        }

        $validation = $coupon->isValidFor($subtotal, $userId);

        if (!$validation['valid']) {
            return response()->json([
                'success' => false,
                'message' => $validation['message'],
            ], 422);
        }

        $discount = $coupon->calculateDiscount($subtotal);

        return response()->json([
            'success' => true,
            'message' => "Coupon '{$coupon->code}' applied successfully!",
            'data' => [
                'coupon_id' => $coupon->id,
                'code' => $coupon->code,
                'type' => $coupon->type,
                'value' => (float) $coupon->value,
                'discount_amount' => $discount,
                'new_subtotal' => max(0, round($subtotal - $discount, 2)),
            ],
        ]);
    }

    /**
     * Admin: List all coupons.
     */
    public function index(Request $request)
    {
        $coupons = Coupon::latest()->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $coupons->items(),
            'meta' => [
                'current_page' => $coupons->currentPage(),
                'last_page' => $coupons->lastPage(),
                'total' => $coupons->total(),
            ],
        ]);
    }

    /**
     * Admin: Store new coupon.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|unique:coupons,code|max:50',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:percentage,fixed,free_shipping',
            'value' => 'required|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'usage_limit' => 'nullable|integer|min:1',
            'usage_limit_per_user' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $data['code'] = strtoupper(trim($data['code']));

        $coupon = Coupon::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Coupon created successfully',
            'data' => $coupon,
        ], 201);
    }

    /**
     * Admin: Update coupon.
     */
    public function update(Request $request, $id)
    {
        $coupon = Coupon::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:50|unique:coupons,code,' . $coupon->id,
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:percentage,fixed,free_shipping',
            'value' => 'required|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'usage_limit' => 'nullable|integer|min:1',
            'usage_limit_per_user' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $data['code'] = strtoupper(trim($data['code']));
        $coupon->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Coupon updated successfully',
            'data' => $coupon,
        ]);
    }

    /**
     * Admin: Delete coupon.
     */
    public function destroy($id)
    {
        $coupon = Coupon::findOrFail($id);
        $coupon->delete();

        return response()->json([
            'success' => true,
            'message' => 'Coupon deleted successfully',
        ]);
    }
}
