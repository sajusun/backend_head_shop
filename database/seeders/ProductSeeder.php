<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Color;
use App\Models\Amenity;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Review;
use App\Models\Size;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::where('type', 'product')->get()->keyBy('name');
        $colors = Color::where('status', 'active')->get();
        $sizes = Size::where('status', 'active')->get();
        $users = User::where('role', 'user')->get();
        $amenities = Amenity::where('status', 'active')->get();
        $imageDirectory = public_path('storage/products_images');
        $images = File::isDirectory($imageDirectory) ? File::files($imageDirectory) : [];

        if ($categories->isEmpty() || $colors->isEmpty() || $sizes->isEmpty() || $users->isEmpty() || $amenities->isEmpty() || count($images) === 0) {
            $this->command?->warn('ProductSeeder skipped: missing dependencies.');
            return;
        }

        $allImages = collect($images)->map(fn ($image) => 'products_images/' . $image->getFilename());
        
        $categorizedImages = [
            'Fashion' => $allImages->filter(fn($p) => str_contains($p, 'cloth_fashion') || str_contains($p, 'shirt') || str_contains($p, 'saree'))->values(),
            'Electronics' => $allImages->filter(fn($p) => str_contains($p, 'electronic') || str_contains($p, 'headphone'))->values(),
            'Home & Living' => $allImages->filter(fn($p) => str_contains($p, 'home_appliance'))->values(),
        ];

        $fallbackImages = $allImages->filter(fn($p) => str_contains($p, 'products_image'))->values();
        if ($fallbackImages->isEmpty()) $fallbackImages = $allImages;

        $products = $this->products();

        DB::transaction(function () use ($products, $categories, $colors, $sizes, $users, $amenities, $categorizedImages, $fallbackImages) {
            foreach ($products as $index => $item) {
                $basePrice = random_int($item['min_price'], $item['max_price']);
                $offerPrice = random_int(1, 100) <= 45 ? $basePrice - random_int(5, min(80, $basePrice - 1)) : null;
                $slug = Str::slug($item['name']) . '-' . uniqid();
                $sku = 'HS-' . str_pad((string) ($index + 1), 6, '0', STR_PAD_LEFT);
                
                // Pick images based on category
                $catImages = $categorizedImages[$item['category']] ?? $fallbackImages;
                if ($catImages->isEmpty()) $catImages = $fallbackImages;
                
                $productImages = [];
                for ($i = 0; $i < 3; $i++) {
                    $productImages[] = $catImages->random();
                }

                $productAmenities = $this->amenitiesFor($amenities, $item['category'], $index);

                $product = Product::create([
                    'category_id' => $categories[$item['category']]->id,
                    'name' => $item['name'],
                    'slug' => $slug,
                    'sku' => $sku,
                    'thumbnail' => $productImages[0],
                    'description' => $this->descriptionFor($item['name'], $item['category']),
                    'price' => $basePrice,
                    'offer_price' => $offerPrice,
                    'amenities' => $productAmenities->map(fn (Amenity $amenity) => [
                        'name' => $amenity->name,
                        'slug' => $amenity->slug,
                        'category' => $amenity->category,
                    ])->values()->all(),
                    'is_featured' => $index < 20 || $index % 15 === 0,
                    'status' => 'published',
                ]);

                $product->amenityItems()->sync($productAmenities->pluck('id')->all());

                foreach ($productImages as $imageIndex => $path) {
                    ProductImage::create([
                        'product_id' => $product->id,
                        'path' => $path,
                        'is_primary' => $imageIndex === 0,
                        'order' => $imageIndex,
                    ]);
                }

                $variantCount = random_int(2, 6);
                $shuffledColors = $colors->shuffle();
                
                for ($v = 0; $v < $variantCount; $v++) {
                    $variantPrice = $basePrice + ($v * random_int(2, 10));
                    $product->variants()->create([
                        'color_id' => $shuffledColors[$v % $shuffledColors->count()]->id,
                        'size_id' => $sizes->random()->id,
                        'price' => $variantPrice,
                        'offer_price' => $offerPrice ? max(1, $offerPrice + ($v * random_int(1, 5))) : null,
                        'stock' => random_int(10, 200),
                        'status' => 'published',
                    ]);
                }

                $reviewCount = random_int(5, 15);
                $shuffledUsers = $users->shuffle();
                
                for ($r = 0; $r < $reviewCount; $r++) {
                    Review::create([
                        'product_id' => $product->id,
                        'user_id' => $shuffledUsers[$r % $shuffledUsers->count()]->id,
                        'rating' => random_int(4, 5),
                        'comment' => $this->reviewComment($r),
                        'status' => 'approved',
                    ]);
                }
            }
        });
    }

    private function pickImages($imagePaths, int $productIndex, int $count): array
    {
        $picked = [];
        $total = $imagePaths->count();

        for ($i = 0; $i < $count; $i++) {
            $picked[] = $imagePaths[(($productIndex * $count) + $i) % $total];
        }

        return $picked;
    }

    private function descriptionFor(string $name, string $category): string
    {
        return "{$name} is selected for everyday ecommerce use in the {$category} category. It balances practical quality, clean styling, reliable value, and easy pairing with other essentials.";
    }

    private function amenitiesFor($amenities, string $category, int $productIndex)
    {
        $global = $amenities->where('category', 'Global')->values();
        $categoryAmenities = $amenities->where('category', $category)->values();

        return $global
            ->slice($productIndex % max(1, $global->count()), 2)
            ->merge($global->slice(0, 2))
            ->merge($categoryAmenities->slice($productIndex % max(1, $categoryAmenities->count()), 3))
            ->merge($categoryAmenities->slice(0, 3))
            ->unique('id')
            ->take(5)
            ->values();
    }

    private function reviewComment(int $index): string
    {
        $comments = [
            'Absolutely love this! The quality is even better than expected.',
            'A bit pricey, but worth every penny for the durability.',
            'Perfect fit and the color is exactly as shown in the pictures.',
            'Fast shipping and great packaging. The product works perfectly.',
            'I have been using this for a week now, and it is holding up great.',
            'The design is so unique. I get compliments on it all the time.',
            'Could be better, but for this price point, I am satisfied.',
            'Highly recommend this to anyone looking for a reliable choice.',
            'The materials feel very premium. Definitely buying again.',
            'Great customer service and the product is top-notch.',
            'Exactly what I needed for my daily routine. Very happy.',
            'The attention to detail is impressive. Well worth the wait.',
            'Simple, elegant, and functional. Everything I wanted.',
            'Exceeded my expectations in every way possible.',
            'A solid 5-star product. No complaints at all.',
        ];

        return $comments[array_rand($comments)];
    }

    private function products(): array
    {
        $categories = [
            'Fashion' => ['Classic', 'Modern', 'Casual', 'Formal', 'Trendy', 'Vintage', 'Urban', 'Elegant', 'Premium', 'Basic'],
            'Electronics' => ['Smart', 'Wireless', 'Portable', 'Digital', 'Pro', 'Elite', 'Next-Gen', 'High-Performance', 'Compact', 'Ultra'],
            'Home & Living' => ['Modern', 'Cozy', 'Minimalist', 'Luxe', 'Handcrafted', 'Sustainable', 'Rustic', 'Essential', 'Designer', 'Functional'],
            'Beauty' => ['Natural', 'Pure', 'Organic', 'Glowing', 'Refresh', 'Revitalize', 'Velvet', 'Silk', 'Aura', 'Bloom'],
            'Sports' => ['Active', 'Power', 'Endurance', 'Flex', 'Speed', 'Agility', 'Extreme', 'Peak', 'Pro-Fit', 'Durable'],
            'Groceries' => ['Fresh', 'Organic', 'Natural', 'Premium', 'Wholesome', 'Farm-Direct', 'Gourmet', 'Healthy', 'Pure', 'Essential'],
            'Toys' => ['Fun', 'Creative', 'Educational', 'Magic', 'Adventure', 'Build', 'Play', 'Smart', 'Classic', 'Joy'],
            'Books' => ['Best-selling', 'Classic', 'Inspiring', 'New Release', 'Limited Edition', 'Collector\'s', 'Educational', 'Masterpiece', 'Essential', 'Must-Read'],
            'Jewelry' => ['Shining', 'Golden', 'Silver', 'Elegant', 'Precious', 'Luxury', 'Artisan', 'Timeless', 'Brilliant', 'Dazzling'],
            'Automotive' => ['Reliable', 'Advanced', 'Premium', 'Eco-friendly', 'Universal', 'Sport', 'Heavy-duty', 'Precision', 'Compact', 'Durable'],
        ];

        $productNames = [
            'Fashion' => ['T-Shirt', 'Jeans', 'Shirt', 'Hoodie', 'Jacket', 'Skirt', 'Pants', 'Sweater', 'Dress', 'Shorts', 'Saree', 'Kurta', 'Blazer'],
            'Electronics' => ['Headphones', 'Watch', 'Power Bank', 'Speaker', 'Cable', 'Earbuds', 'Webcam', 'Keyboard', 'Mouse', 'Tablet', 'Laptop', 'Camera'],
            'Home & Living' => ['Dinner Set', 'Bed Sheet', 'Pillow', 'Lamp', 'Cookware', 'Basket', 'Candle', 'Shelf', 'Towel Set', 'Storage Jars', 'Mirror', 'Rug'],
            'Beauty' => ['Serum', 'Lipstick', 'Sunscreen', 'Cleanser', 'Hair Oil', 'Mascara', 'Toner', 'Body Butter', 'Brush Set', 'Night Cream', 'Perfume', 'Lotion'],
            'Sports' => ['Yoga Mat', 'Dumbbells', 'Running Shoes', 'Gym Towel', 'Water Bottle', 'Resistance Bands', 'Helmet', 'Football', 'Shorts', 'Gloves', 'Tracksuit'],
            'Groceries' => ['Rice', 'Olive Oil', 'Green Tea', 'Honey', 'Pasta', 'Peanut Butter', 'Dry Fruit', 'Coffee', 'Cereal', 'Salt', 'Sugar', 'Milk'],
            'Toys' => ['Building Blocks', 'Racing Car', 'Teddy Bear', 'Puzzle Set', 'Art Kit', 'Basketball Hoop', 'Magnetic Tiles', 'Doll House', 'Science Kit', 'Keyboard'],
            'Books' => ['Fiction', 'Guide', 'Cookbook', 'Story Collection', 'Handbook', 'Photography Book', 'Journal', 'Workbook', 'Literature', 'Playbook'],
            'Jewelry' => ['Bracelet', 'Ring', 'Earrings', 'Necklace', 'Pendant', 'Anklet', 'Couple Rings', 'Charm Bracelet', 'Watch Strap', 'Bangle'],
            'Automotive' => ['Phone Mount', 'Cleaning Cloth', 'Tire Inflator', 'Steering Cover', 'Vacuum Cleaner', 'Organizer', 'Headlight', 'Floor Mats', 'Repair Pen', 'Tool Kit'],
        ];

        $products = [];
        $count = 360; // Generate 360 products

        $catKeys = array_keys($categories);
        
        for ($i = 0; $i < $count; $i++) {
            $cat = $catKeys[$i % count($catKeys)];
            $prefix = $categories[$cat][array_rand($categories[$cat])];
            $name = $productNames[$cat][array_rand($productNames[$cat])];
            
            $products[] = [
                'category' => $cat,
                'name' => "{$prefix} {$name} " . ($i + 1),
                'min_price' => rand(10, 50),
                'max_price' => rand(60, 300),
            ];
        }

        return $products;
    }
}
