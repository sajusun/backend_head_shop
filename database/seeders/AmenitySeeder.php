<?php

namespace Database\Seeders;

use App\Models\Amenity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AmenitySeeder extends Seeder
{
    public function run(): void
    {
        $amenities = [
            ['category' => 'Global', 'name' => 'Fast Delivery'],
            ['category' => 'Global', 'name' => 'Easy Return'],
            ['category' => 'Global', 'name' => 'Secure Checkout'],
            ['category' => 'Global', 'name' => 'Authentic Product'],
            ['category' => 'Global', 'name' => 'Quality Checked'],
            ['category' => 'Fashion', 'name' => 'Soft Fabric'],
            ['category' => 'Fashion', 'name' => 'Comfort Fit'],
            ['category' => 'Fashion', 'name' => 'Machine Washable'],
            ['category' => 'Fashion', 'name' => 'Breathable Material'],
            ['category' => 'Fashion', 'name' => 'Everyday Wear'],
            ['category' => 'Electronics', 'name' => 'Warranty Included'],
            ['category' => 'Electronics', 'name' => 'Energy Efficient'],
            ['category' => 'Electronics', 'name' => 'Compact Design'],
            ['category' => 'Electronics', 'name' => 'Quick Setup'],
            ['category' => 'Electronics', 'name' => 'Reliable Battery Life'],
            ['category' => 'Home & Living', 'name' => 'Durable Build'],
            ['category' => 'Home & Living', 'name' => 'Easy To Clean'],
            ['category' => 'Home & Living', 'name' => 'Space Saving'],
            ['category' => 'Home & Living', 'name' => 'Modern Design'],
            ['category' => 'Home & Living', 'name' => 'Family Friendly'],
            ['category' => 'Beauty', 'name' => 'Dermatologically Tested'],
            ['category' => 'Beauty', 'name' => 'Gentle Formula'],
            ['category' => 'Beauty', 'name' => 'Hydrating Care'],
            ['category' => 'Beauty', 'name' => 'Travel Friendly'],
            ['category' => 'Beauty', 'name' => 'Daily Use'],
            ['category' => 'Sports', 'name' => 'Lightweight'],
            ['category' => 'Sports', 'name' => 'Sweat Resistant'],
            ['category' => 'Sports', 'name' => 'Grip Support'],
            ['category' => 'Sports', 'name' => 'Outdoor Ready'],
            ['category' => 'Sports', 'name' => 'Training Friendly'],
            ['category' => 'Groceries', 'name' => 'Fresh Stock'],
            ['category' => 'Groceries', 'name' => 'Hygienic Packaging'],
            ['category' => 'Groceries', 'name' => 'Pantry Essential'],
            ['category' => 'Groceries', 'name' => 'Premium Quality'],
            ['category' => 'Groceries', 'name' => 'Long Shelf Life'],
            ['category' => 'Toys', 'name' => 'Child Safe'],
            ['category' => 'Toys', 'name' => 'Educational Value'],
            ['category' => 'Toys', 'name' => 'Bright Colors'],
            ['category' => 'Toys', 'name' => 'Durable Parts'],
            ['category' => 'Toys', 'name' => 'Gift Ready'],
            ['category' => 'Books', 'name' => 'Paperback Edition'],
            ['category' => 'Books', 'name' => 'Easy Reading'],
            ['category' => 'Books', 'name' => 'Informative Content'],
            ['category' => 'Books', 'name' => 'Giftable Pick'],
            ['category' => 'Books', 'name' => 'Compact Size'],
            ['category' => 'Jewelry', 'name' => 'Polished Finish'],
            ['category' => 'Jewelry', 'name' => 'Gift Box Included'],
            ['category' => 'Jewelry', 'name' => 'Skin Friendly'],
            ['category' => 'Jewelry', 'name' => 'Elegant Design'],
            ['category' => 'Jewelry', 'name' => 'Adjustable Fit'],
            ['category' => 'Automotive', 'name' => 'Easy Installation'],
            ['category' => 'Automotive', 'name' => 'Vehicle Friendly'],
            ['category' => 'Automotive', 'name' => 'Weather Resistant'],
            ['category' => 'Automotive', 'name' => 'Compact Storage'],
            ['category' => 'Automotive', 'name' => 'Road Trip Ready'],
        ];

        foreach ($amenities as $amenity) {
            Amenity::updateOrCreate(
                ['slug' => Str::slug($amenity['category'] . '-' . $amenity['name'])],
                [
                    'name' => $amenity['name'],
                    'category' => $amenity['category'],
                    'status' => 'active',
                ]
            );
        }
    }
}
