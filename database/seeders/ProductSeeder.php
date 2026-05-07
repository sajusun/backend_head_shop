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
            $this->command?->warn('ProductSeeder skipped: categories, colors, sizes, users, amenities, and product images are required.');
            return;
        }

        $imagePaths = collect($images)
            ->map(fn ($image) => 'products_images/' . $image->getFilename())
            ->sort(SORT_NATURAL)
            ->values();

        $products = $this->products();

        DB::transaction(function () use ($products, $categories, $colors, $sizes, $users, $amenities, $imagePaths) {
            foreach ($products as $index => $item) {
                $basePrice = random_int($item['min_price'], $item['max_price']);
                $offerPrice = random_int(1, 100) <= 45 ? $basePrice - random_int(5, min(80, $basePrice - 1)) : null;
                $slug = Str::slug($item['name']);
                $sku = 'HS-' . str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT);
                $productImages = $this->pickImages($imagePaths, $index, 3);
                $productAmenities = $this->amenitiesFor($amenities, $item['category'], $index);

                $product = Product::updateOrCreate(
                    ['sku' => $sku],
                    [
                        'category_id' => $categories[$item['category']]->id,
                        'name' => $item['name'],
                        'slug' => $slug,
                        'thumbnail' => $productImages[0],
                        'description' => $this->descriptionFor($item['name'], $item['category']),
                        'price' => $basePrice,
                        'offer_price' => $offerPrice,
                        'amenities' => $productAmenities->map(fn (Amenity $amenity) => [
                            'name' => $amenity->name,
                            'slug' => $amenity->slug,
                            'category' => $amenity->category,
                        ])->values()->all(),
                        'is_featured' => $index < 18 || $index % 9 === 0,
                        'status' => 'published',
                    ]
                );

                $product->images()->delete();
                $product->variants()->delete();
                $product->reviews()->delete();
                $product->amenityItems()->sync($productAmenities->pluck('id')->all());

                foreach ($productImages as $imageIndex => $path) {
                    ProductImage::create([
                        'product_id' => $product->id,
                        'path' => $path,
                        'is_primary' => $imageIndex === 0,
                        'order' => $imageIndex,
                    ]);
                }

                $variantCount = random_int(2, 5);
                $variantCombos = $colors->shuffle()
                    ->take($variantCount)
                    ->values()
                    ->map(fn ($color, $comboIndex) => [
                        'color_id' => $color->id,
                        'size_id' => $sizes[($index + $comboIndex) % $sizes->count()]->id,
                    ]);

                foreach ($variantCombos as $comboIndex => $combo) {
                    $variantPrice = $basePrice + ($comboIndex * random_int(2, 12));

                    $product->variants()->create([
                        'color_id' => $combo['color_id'],
                        'size_id' => $combo['size_id'],
                        'price' => $variantPrice,
                        'offer_price' => $offerPrice ? max(1, $offerPrice + ($comboIndex * random_int(1, 8))) : null,
                        'stock' => random_int(8, 120),
                        'status' => 'published',
                    ]);
                }

                $reviewCount = random_int(3, 20);

                for ($reviewIndex = 0; $reviewIndex < $reviewCount; $reviewIndex++) {
                    Review::create([
                        'product_id' => $product->id,
                        'user_id' => $users[($index + $reviewIndex) % $users->count()]->id,
                        'rating' => random_int(3, 5),
                        'comment' => $this->reviewComment($reviewIndex),
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
            'Good quality for the price and arrived in nice condition.',
            'Looks exactly as expected and feels reliable for daily use.',
            'The finish is clean, and the product feels better than average.',
            'Happy with the purchase. Packaging and delivery were both smooth.',
            'Useful item, easy to match with the rest of my setup.',
            'Solid value. I would consider buying another one.',
            'The product feels premium enough and works well so far.',
            'Nice design, comfortable to use, and worth the money.',
            'No complaints. It matched the description and photos.',
            'A dependable choice if you want something simple and practical.',
        ];

        return $comments[$index % count($comments)];
    }

    private function products(): array
    {
        return [
            ['category' => 'Fashion', 'name' => 'Classic Cotton T-Shirt', 'min_price' => 18, 'max_price' => 35],
            ['category' => 'Fashion', 'name' => 'Slim Fit Denim Jeans', 'min_price' => 42, 'max_price' => 95],
            ['category' => 'Fashion', 'name' => 'Casual Linen Shirt', 'min_price' => 34, 'max_price' => 72],
            ['category' => 'Fashion', 'name' => 'Everyday Hoodie', 'min_price' => 45, 'max_price' => 110],
            ['category' => 'Fashion', 'name' => 'Lightweight Bomber Jacket', 'min_price' => 75, 'max_price' => 180],
            ['category' => 'Fashion', 'name' => 'Pleated Midi Skirt', 'min_price' => 38, 'max_price' => 88],
            ['category' => 'Fashion', 'name' => 'Tailored Chino Pants', 'min_price' => 42, 'max_price' => 100],
            ['category' => 'Fashion', 'name' => 'Ribbed Knit Sweater', 'min_price' => 48, 'max_price' => 120],
            ['category' => 'Fashion', 'name' => 'Athletic Jogger Pants', 'min_price' => 36, 'max_price' => 85],
            ['category' => 'Fashion', 'name' => 'Formal Oxford Shirt', 'min_price' => 44, 'max_price' => 95],
            ['category' => 'Electronics', 'name' => 'Wireless Bluetooth Headphones', 'min_price' => 55, 'max_price' => 190],
            ['category' => 'Electronics', 'name' => 'Smart Fitness Watch', 'min_price' => 70, 'max_price' => 240],
            ['category' => 'Electronics', 'name' => 'Portable Power Bank', 'min_price' => 24, 'max_price' => 80],
            ['category' => 'Electronics', 'name' => 'Compact Bluetooth Speaker', 'min_price' => 32, 'max_price' => 130],
            ['category' => 'Electronics', 'name' => 'Fast Charging USB-C Cable', 'min_price' => 9, 'max_price' => 28],
            ['category' => 'Electronics', 'name' => 'Noise Cancelling Earbuds', 'min_price' => 65, 'max_price' => 220],
            ['category' => 'Electronics', 'name' => 'Wireless Charging Pad', 'min_price' => 20, 'max_price' => 65],
            ['category' => 'Electronics', 'name' => 'HD Webcam', 'min_price' => 38, 'max_price' => 140],
            ['category' => 'Electronics', 'name' => 'Mechanical Keyboard', 'min_price' => 58, 'max_price' => 180],
            ['category' => 'Electronics', 'name' => 'Ergonomic Wireless Mouse', 'min_price' => 24, 'max_price' => 95],
            ['category' => 'Home & Living', 'name' => 'Ceramic Dinner Plate Set', 'min_price' => 30, 'max_price' => 95],
            ['category' => 'Home & Living', 'name' => 'Soft Cotton Bed Sheet', 'min_price' => 40, 'max_price' => 120],
            ['category' => 'Home & Living', 'name' => 'Decorative Throw Pillow', 'min_price' => 18, 'max_price' => 55],
            ['category' => 'Home & Living', 'name' => 'Modern Table Lamp', 'min_price' => 45, 'max_price' => 160],
            ['category' => 'Home & Living', 'name' => 'Nonstick Cookware Set', 'min_price' => 80, 'max_price' => 260],
            ['category' => 'Home & Living', 'name' => 'Bamboo Storage Basket', 'min_price' => 22, 'max_price' => 75],
            ['category' => 'Home & Living', 'name' => 'Scented Soy Candle', 'min_price' => 12, 'max_price' => 38],
            ['category' => 'Home & Living', 'name' => 'Wall Mounted Shelf', 'min_price' => 28, 'max_price' => 110],
            ['category' => 'Home & Living', 'name' => 'Microfiber Bath Towel Set', 'min_price' => 24, 'max_price' => 80],
            ['category' => 'Home & Living', 'name' => 'Glass Food Storage Jars', 'min_price' => 18, 'max_price' => 60],
            ['category' => 'Beauty', 'name' => 'Hydrating Face Serum', 'min_price' => 18, 'max_price' => 70],
            ['category' => 'Beauty', 'name' => 'Matte Liquid Lipstick', 'min_price' => 10, 'max_price' => 35],
            ['category' => 'Beauty', 'name' => 'Daily Sunscreen Lotion', 'min_price' => 14, 'max_price' => 45],
            ['category' => 'Beauty', 'name' => 'Gentle Facial Cleanser', 'min_price' => 12, 'max_price' => 40],
            ['category' => 'Beauty', 'name' => 'Nourishing Hair Oil', 'min_price' => 9, 'max_price' => 32],
            ['category' => 'Beauty', 'name' => 'Volumizing Mascara', 'min_price' => 12, 'max_price' => 42],
            ['category' => 'Beauty', 'name' => 'Rose Water Toner', 'min_price' => 8, 'max_price' => 30],
            ['category' => 'Beauty', 'name' => 'Moisturizing Body Butter', 'min_price' => 16, 'max_price' => 48],
            ['category' => 'Beauty', 'name' => 'Makeup Brush Set', 'min_price' => 22, 'max_price' => 90],
            ['category' => 'Beauty', 'name' => 'Repairing Night Cream', 'min_price' => 20, 'max_price' => 85],
            ['category' => 'Sports', 'name' => 'Yoga Mat With Carry Strap', 'min_price' => 22, 'max_price' => 70],
            ['category' => 'Sports', 'name' => 'Adjustable Dumbbell Pair', 'min_price' => 55, 'max_price' => 180],
            ['category' => 'Sports', 'name' => 'Running Training Shoes', 'min_price' => 60, 'max_price' => 180],
            ['category' => 'Sports', 'name' => 'Breathable Gym Towel', 'min_price' => 10, 'max_price' => 32],
            ['category' => 'Sports', 'name' => 'Stainless Steel Water Bottle', 'min_price' => 14, 'max_price' => 45],
            ['category' => 'Sports', 'name' => 'Resistance Band Set', 'min_price' => 16, 'max_price' => 55],
            ['category' => 'Sports', 'name' => 'Cycling Safety Helmet', 'min_price' => 38, 'max_price' => 130],
            ['category' => 'Sports', 'name' => 'Football Training Ball', 'min_price' => 18, 'max_price' => 65],
            ['category' => 'Sports', 'name' => 'Quick Dry Sports Shorts', 'min_price' => 20, 'max_price' => 60],
            ['category' => 'Sports', 'name' => 'Fitness Grip Gloves', 'min_price' => 12, 'max_price' => 45],
            ['category' => 'Groceries', 'name' => 'Organic Basmati Rice', 'min_price' => 12, 'max_price' => 35],
            ['category' => 'Groceries', 'name' => 'Extra Virgin Olive Oil', 'min_price' => 14, 'max_price' => 45],
            ['category' => 'Groceries', 'name' => 'Premium Green Tea', 'min_price' => 8, 'max_price' => 28],
            ['category' => 'Groceries', 'name' => 'Natural Honey Jar', 'min_price' => 9, 'max_price' => 30],
            ['category' => 'Groceries', 'name' => 'Whole Grain Pasta', 'min_price' => 4, 'max_price' => 18],
            ['category' => 'Groceries', 'name' => 'Crunchy Peanut Butter', 'min_price' => 6, 'max_price' => 22],
            ['category' => 'Groceries', 'name' => 'Mixed Dry Fruit Pack', 'min_price' => 12, 'max_price' => 45],
            ['category' => 'Groceries', 'name' => 'Arabica Coffee Beans', 'min_price' => 15, 'max_price' => 55],
            ['category' => 'Groceries', 'name' => 'Breakfast Cereal Box', 'min_price' => 5, 'max_price' => 20],
            ['category' => 'Groceries', 'name' => 'Himalayan Pink Salt', 'min_price' => 4, 'max_price' => 16],
            ['category' => 'Toys', 'name' => 'Wooden Building Blocks', 'min_price' => 18, 'max_price' => 65],
            ['category' => 'Toys', 'name' => 'Remote Control Racing Car', 'min_price' => 30, 'max_price' => 120],
            ['category' => 'Toys', 'name' => 'Plush Teddy Bear', 'min_price' => 16, 'max_price' => 55],
            ['category' => 'Toys', 'name' => 'Educational Puzzle Set', 'min_price' => 12, 'max_price' => 45],
            ['category' => 'Toys', 'name' => 'Kids Art Supply Kit', 'min_price' => 14, 'max_price' => 50],
            ['category' => 'Toys', 'name' => 'Mini Basketball Hoop', 'min_price' => 20, 'max_price' => 75],
            ['category' => 'Toys', 'name' => 'Magnetic Tile Set', 'min_price' => 25, 'max_price' => 95],
            ['category' => 'Toys', 'name' => 'Doll House Furniture Set', 'min_price' => 28, 'max_price' => 110],
            ['category' => 'Toys', 'name' => 'Science Experiment Kit', 'min_price' => 18, 'max_price' => 70],
            ['category' => 'Toys', 'name' => 'Musical Toy Keyboard', 'min_price' => 22, 'max_price' => 85],
            ['category' => 'Books', 'name' => 'Modern Fiction Paperback', 'min_price' => 8, 'max_price' => 25],
            ['category' => 'Books', 'name' => 'Personal Finance Guide', 'min_price' => 12, 'max_price' => 35],
            ['category' => 'Books', 'name' => 'Healthy Cooking Cookbook', 'min_price' => 14, 'max_price' => 42],
            ['category' => 'Books', 'name' => 'Children Story Collection', 'min_price' => 10, 'max_price' => 30],
            ['category' => 'Books', 'name' => 'Business Strategy Handbook', 'min_price' => 18, 'max_price' => 55],
            ['category' => 'Books', 'name' => 'Travel Photography Book', 'min_price' => 16, 'max_price' => 50],
            ['category' => 'Books', 'name' => 'Mindfulness Journal', 'min_price' => 9, 'max_price' => 28],
            ['category' => 'Books', 'name' => 'Language Learning Workbook', 'min_price' => 10, 'max_price' => 32],
            ['category' => 'Books', 'name' => 'Classic Literature Edition', 'min_price' => 12, 'max_price' => 40],
            ['category' => 'Books', 'name' => 'Tech Career Playbook', 'min_price' => 15, 'max_price' => 48],
            ['category' => 'Jewelry', 'name' => 'Minimal Gold Bracelet', 'min_price' => 35, 'max_price' => 140],
            ['category' => 'Jewelry', 'name' => 'Sterling Silver Ring', 'min_price' => 28, 'max_price' => 120],
            ['category' => 'Jewelry', 'name' => 'Pearl Drop Earrings', 'min_price' => 32, 'max_price' => 130],
            ['category' => 'Jewelry', 'name' => 'Layered Chain Necklace', 'min_price' => 38, 'max_price' => 160],
            ['category' => 'Jewelry', 'name' => 'Crystal Pendant Set', 'min_price' => 25, 'max_price' => 100],
            ['category' => 'Jewelry', 'name' => 'Classic Hoop Earrings', 'min_price' => 18, 'max_price' => 80],
            ['category' => 'Jewelry', 'name' => 'Adjustable Anklet', 'min_price' => 16, 'max_price' => 65],
            ['category' => 'Jewelry', 'name' => 'Engraved Couple Rings', 'min_price' => 45, 'max_price' => 180],
            ['category' => 'Jewelry', 'name' => 'Gemstone Charm Bracelet', 'min_price' => 30, 'max_price' => 125],
            ['category' => 'Jewelry', 'name' => 'Luxury Watch Strap', 'min_price' => 40, 'max_price' => 150],
            ['category' => 'Automotive', 'name' => 'Car Phone Mount', 'min_price' => 12, 'max_price' => 40],
            ['category' => 'Automotive', 'name' => 'Microfiber Cleaning Cloth Set', 'min_price' => 8, 'max_price' => 28],
            ['category' => 'Automotive', 'name' => 'Portable Tire Inflator', 'min_price' => 35, 'max_price' => 120],
            ['category' => 'Automotive', 'name' => 'Leather Steering Wheel Cover', 'min_price' => 18, 'max_price' => 65],
            ['category' => 'Automotive', 'name' => 'Car Vacuum Cleaner', 'min_price' => 30, 'max_price' => 110],
            ['category' => 'Automotive', 'name' => 'Dashboard Organizer Tray', 'min_price' => 10, 'max_price' => 35],
            ['category' => 'Automotive', 'name' => 'LED Headlight Bulb Pair', 'min_price' => 24, 'max_price' => 90],
            ['category' => 'Automotive', 'name' => 'All Weather Floor Mats', 'min_price' => 38, 'max_price' => 140],
            ['category' => 'Automotive', 'name' => 'Scratch Repair Pen', 'min_price' => 6, 'max_price' => 20],
            ['category' => 'Automotive', 'name' => 'Car Emergency Tool Kit', 'min_price' => 28, 'max_price' => 100],
        ];
    }
}
