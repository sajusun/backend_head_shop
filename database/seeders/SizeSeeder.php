<?php

namespace Database\Seeders;

use App\Models\Size;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SizeSeeder extends Seeder
{
    public function run(): void
    {
        $sizes = [
            // Standard Tops / Adults
            ['name' => 'Extra Small', 'short' => 'XS'],
            ['name' => 'Small', 'short' => 'S'],
            ['name' => 'Medium', 'short' => 'M'],
            ['name' => 'Large', 'short' => 'L'],
            ['name' => 'Extra Large', 'short' => 'XL'],
            ['name' => 'Double Extra Large', 'short' => 'XXL'],
            ['name' => 'Triple Extra Large', 'short' => '3XL'],

            // Bottoms Waist
            ['name' => 'Waist 28', 'short' => '28'],
            ['name' => 'Waist 30', 'short' => '30'],
            ['name' => 'Waist 32', 'short' => '32'],
            ['name' => 'Waist 34', 'short' => '34'],
            ['name' => 'Waist 36', 'short' => '36'],
            ['name' => 'Waist 38', 'short' => '38'],

            // Kids & Baby
            ['name' => '0-3 Months', 'short' => '0-3M'],
            ['name' => '3-6 Months', 'short' => '3-6M'],
            ['name' => '6-12 Months', 'short' => '6-12M'],
            ['name' => '1-2 Years', 'short' => '1-2Y'],
            ['name' => '3-4 Years', 'short' => '3-4Y'],
            ['name' => '5-6 Years', 'short' => '5-6Y'],
            ['name' => '7-8 Years', 'short' => '7-8Y'],
            ['name' => '9-10 Years', 'short' => '9-10Y'],
            ['name' => '11-12 Years', 'short' => '11-12Y'],
        ];

        foreach ($sizes as $size) {
            Size::updateOrCreate(
                ['short' => $size['short']],
                [
                    'name' => $size['name'],
                    'slug' => Str::slug($size['name']),
                    'status' => 'active',
                ]
            );
        }
    }
}
