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
            // Men's categories
            ['name' => "Men's T-Shirts & Polos", 'department' => 'men', 'slug' => 'mens-t-shirts-polos'],
            ['name' => "Men's Casual & Formal Shirts", 'department' => 'men', 'slug' => 'mens-shirts'],
            ['name' => "Men's Panjabi & Ethnic", 'department' => 'men', 'slug' => 'mens-panjabi-ethnic'],
            ['name' => "Men's Jeans & Chinos", 'department' => 'men', 'slug' => 'mens-jeans-chinos'],
            ['name' => "Men's Hoodies & Jackets", 'department' => 'men', 'slug' => 'mens-hoodies-jackets'],
            ['name' => "Men's Activewear & Shorts", 'department' => 'men', 'slug' => 'mens-activewear-shorts'],

            // Women's categories
            ['name' => "Women's Kurtis & Tunics", 'department' => 'women', 'slug' => 'womens-kurtis-tunics'],
            ['name' => "Women's Sarees", 'department' => 'women', 'slug' => 'womens-sarees'],
            ['name' => "Women's Tops & T-Shirts", 'department' => 'women', 'slug' => 'womens-tops-tshirts'],
            ['name' => "Women's Dresses & Co-ord Sets", 'department' => 'women', 'slug' => 'womens-dresses-coord-sets'],
            ['name' => "Women's Jeans & Palazzos", 'department' => 'women', 'slug' => 'womens-jeans-palazzos'],
            ['name' => "Women's Winterwear & Shrugs", 'department' => 'women', 'slug' => 'womens-winterwear-shrugs'],

            // Kids & Baby categories
            ['name' => "Boys Clothing (2-14Y)", 'department' => 'kids', 'slug' => 'boys-clothing'],
            ['name' => "Girls Clothing (2-14Y)", 'department' => 'kids', 'slug' => 'girls-clothing'],
            ['name' => "Baby & Infant Sets (0-24M)", 'department' => 'baby', 'slug' => 'baby-infant-sets'],
            ['name' => "Kids Ethnic & Partywear", 'department' => 'kids', 'slug' => 'kids-ethnic-partywear'],
        ];

        foreach ($categories as $cat) {
            Category::updateOrCreate(
                ['slug' => $cat['slug']],
                [
                    'name' => $cat['name'],
                    'department' => $cat['department'],
                    'type' => 'product',
                    'status' => 'active',
                ]
            );
        }
    }
}
