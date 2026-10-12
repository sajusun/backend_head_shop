<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'title',
        'description',
        'type',
        'value',
        'min_order_amount',
        'max_discount_amount',
        'start_date',
        'end_date',
        'usage_limit',
        'usage_limit_per_user',
        'used_count',
        'is_active',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'usage_limit' => 'integer',
        'usage_limit_per_user' => 'integer',
        'used_count' => 'integer',
        'is_active' => 'boolean',
    ];

    public function usages()
    {
        return $this->hasMany(CouponUsage::class);
    }

    /**
     * Check if coupon is valid for a given subtotal and user.
     */
    public function isValidFor(float $subtotal, ?int $userId = null): array
    {
        if (!$this->is_active) {
            return ['valid' => false, 'message' => 'Coupon is not active'];
        }

        $now = Carbon::now();
        if ($this->start_date && $now->lt($this->start_date)) {
            return ['valid' => false, 'message' => 'Coupon is not active yet'];
        }

        if ($this->end_date && $now->gt($this->end_date)) {
            return ['valid' => false, 'message' => 'Coupon has expired'];
        }

        if ($this->usage_limit && $this->used_count >= $this->usage_limit) {
            return ['valid' => false, 'message' => 'Coupon usage limit has been reached'];
        }

        if ($subtotal < $this->min_order_amount) {
            return [
                'valid' => false,
                'message' => 'Minimum order amount for this coupon is ' . number_format($this->min_order_amount, 2),
            ];
        }

        if ($userId && $this->usage_limit_per_user) {
            $userUsage = $this->usages()->where('user_id', $userId)->count();
            if ($userUsage >= $this->usage_limit_per_user) {
                return ['valid' => false, 'message' => 'You have already used this coupon maximum times'];
            }
        }

        return ['valid' => true, 'message' => 'Coupon is valid'];
    }

    /**
     * Calculate discount amount on subtotal.
     */
    public function calculateDiscount(float $subtotal): float
    {
        $discount = 0;

        if ($this->type === 'percentage') {
            $discount = ($subtotal * $this->value) / 100;
            if ($this->max_discount_amount && $discount > $this->max_discount_amount) {
                $discount = (float) $this->max_discount_amount;
            }
        } elseif ($this->type === 'fixed') {
            $discount = min($this->value, $subtotal);
        } elseif ($this->type === 'free_shipping') {
            $discount = 0; // handled at shipping level
        }

        return round($discount, 2);
    }
}
