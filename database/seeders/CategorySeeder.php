<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Fashion',
            'Electronics',
            'Home & Living',
            'Beauty',
            'Sports',
            'Groceries',
            'Toys',
            'Books',
            'Jewelry',
            'Automotive',
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['slug' => Str::slug($category)],
                [
                    'name' => $category,
                    'type' => 'product',
                    'status' => 'active',
                ]
            );
        }
    }
}
