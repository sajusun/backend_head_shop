<?php

namespace Database\Seeders;

use App\Models\Coupon;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    public function run(): void
    {
        $coupons = [
            [
                'code' => 'HERD10',
                'title' => 'Welcome 10% Off',
                'description' => 'Get 10% instant discount on your order across any apparel.',
                'type' => 'percentage',
                'value' => 10.00,
                'min_order_amount' => 500.00,
                'max_discount_amount' => 300.00,
                'start_date' => now(),
                'end_date' => now()->addMonths(6),
                'usage_limit' => 1000,
                'usage_limit_per_user' => 2,
                'is_active' => true,
            ],
            [
                'code' => 'FIRSTBUY',
                'title' => 'First Order Special',
                'description' => 'Special 15% discount for new customers.',
                'type' => 'percentage',
                'value' => 15.00,
                'min_order_amount' => 800.00,
                'max_discount_amount' => 500.00,
                'start_date' => now(),
                'end_date' => now()->addMonths(12),
                'usage_limit' => 5000,
                'usage_limit_per_user' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'FREESHIP',
                'title' => 'Free Shipping Nationwide',
                'description' => 'Enjoy 100% free delivery on minimum order of 1500.',
                'type' => 'free_shipping',
                'value' => 0.00,
                'min_order_amount' => 1500.00,
                'max_discount_amount' => null,
                'start_date' => now(),
                'end_date' => now()->addMonths(6),
                'usage_limit' => 2000,
                'usage_limit_per_user' => 3,
                'is_active' => true,
            ],
            [
                'code' => 'FLAT200',
                'title' => 'Flat 200 Cash Discount',
                'description' => 'Save 200 on cart value of 2000 or more.',
                'type' => 'fixed',
                'value' => 200.00,
                'min_order_amount' => 2000.00,
                'max_discount_amount' => 200.00,
                'start_date' => now(),
                'end_date' => now()->addMonths(3),
                'usage_limit' => 500,
                'usage_limit_per_user' => 1,
                'is_active' => true,
            ],
        ];

        foreach ($coupons as $coupon) {
            Coupon::updateOrCreate(
                ['code' => $coupon['code']],
                $coupon
            );
        }
    }
}
