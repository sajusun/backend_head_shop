<?php

namespace Database\Seeders;

use App\Models\Blog;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class BlogSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();
        if (!$admin) {
            $admin = User::factory()->create(['role' => 'admin', 'name' => 'Admin User']);
        }

        $users = User::where('role', 'user')->get();
        if ($users->isEmpty()) {
            $users = User::factory()->count(10)->create();
        }

        $categories = [
            'Electrical' => 'latest-tech-and-electrical-innovation',
            'Fashion' => 'fashion-trends-and-style-guides',
            'About Site' => 'inside-herd-shop-our-journey',
            'Celebrity' => 'celebrity-lifestyle-and-spotlight',
            'Food' => 'culinary-adventures-and-recipes',
            'Environment' => 'sustainability-and-green-living',
        ];

        $blogCategories = [];
        foreach ($categories as $name => $slug) {
            $blogCategories[$name] = Category::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'type' => 'blog',
                    'status' => 'active',
                ]
            );
        }

        $themes = [
            'Electrical' => [
                'The Revolution of Smart Home Electrical Systems',
                'Top 10 Energy-Saving Gadgets for 2026',
                'Understanding Voltage: A Guide for Homeowners',
                'Future of Wireless Power Transmission',
                'Electrical Safety Tips Every Family Should Know',
                'The Rise of Electric Vehicles and Home Charging',
                'Solar Power: Integrating Renewable Energy at Home',
                'High-Tech Wiring Solutions for Modern Offices',
            ],
            'Fashion' => [
                'Sustainable Fabrics: The Future of Fashion',
                'Accessorizing Like a Pro: A Summer Guide',
                'The Return of Vintage: 90s Trends We Love',
                'Building a Capsule Wardrobe on a Budget',
                'Fashion Week Highlights: Global Trends to Watch',
                'The Art of Minimalist Dressing',
                'Choosing the Right Fabric for Every Season',
                'Footwear Essentials for the Urban Professional',
            ],
            'About Site' => [
                'Welcome to Herd Shop: Our Mission and Vision',
                'Behind the Scenes: How We Source Our Products',
                'Customer Spotlight: Why They Choose Herd Shop',
                'Our Commitment to Quality and Sustainability',
                'Expanding Our Reach: New Categories Coming Soon',
                'A Message from Our Founder',
            ],
            'Celebrity' => [
                'Iconic Fashion Moments on the Red Carpet',
                'Celebrity Homes: A Peak into Luxury Living',
                'Top 5 Celebrity Entrepreneurs of the Year',
                'Influence and Impact: Celebs Driving Social Change',
                'Secret Routines of Successful Celebrities',
                'Hollywood Style: From Screen to Street',
            ],
            'Food' => [
                'Exploring the Flavors of Mediterranean Cuisine',
                'Quick and Healthy Breakfast Ideas for Busy Mornings',
                'The Science of Baking: Tips for Perfect Results',
                'Farm to Table: The Importance of Local Sourcing',
                'Superfoods You Should Add to Your Diet Today',
                'Mastering the Art of Homemade Pasta',
                'Coffee Culture: A Global Journey',
            ],
            'Environment' => [
                'Reducing Your Carbon Footprint: Simple Steps',
                'Zero Waste Living: A Beginner\'s Guide',
                'The Impact of Plastic on Our Oceans',
                'Urban Gardening: Growing Food in Small Spaces',
                'Eco-Friendly Travel: Exploring Responsibly',
                'The Importance of Biodiversity in Our Ecosystem',
            ],
        ];

        $imageDirectory = public_path('storage/products_images');
        $allImages = File::isDirectory($imageDirectory) ? File::files($imageDirectory) : [];
        $imagePaths = collect($allImages)->map(fn ($image) => 'products_images/' . $image->getFilename());

        $postCount = 45;
        $themeNames = array_keys($themes);

        for ($i = 0; $i < $postCount; $i++) {
            $theme = $themeNames[$i % count($themeNames)];
            $title = $themes[$theme][array_rand($themes[$theme])] . " " . ($i + 1);
            $slug = Str::slug($title) . '-' . uniqid();
            
            $content = "<h2>Introduction</h2><p>In this article, we explore the fascinating world of {$theme}. Specifically, we'll dive deep into <strong>{$title}</strong>.</p>";
            $content .= "<p>" . fake()->paragraphs(3, true) . "</p>";
            $content .= "<blockquote>" . fake()->sentence() . "</blockquote>";
            $content .= "<h3>Key Insights</h3><p>" . fake()->paragraphs(4, true) . "</p>";
            $content .= "<h4>Conclusion</h4><p>" . fake()->paragraphs(2, true) . "</p>";

            $blog = Blog::create([
                'author_id' => $admin->id,
                'category_id' => $blogCategories[$theme]->id,
                'title' => $title,
                'slug' => $slug,
                'excerpt' => fake()->sentence(20),
                'content' => $content,
                'featured_image' => $imagePaths->random(),
                'status' => 'published',
                'likes_count' => 0, // Will be updated by likes
                'views_count' => rand(100, 5000),
                'published_at' => now()->subDays(rand(1, 100)),
            ]);

            // Add Likes (10-40 per post)
            $likeCount = rand(10, 40);
            $shuffledUsers = $users->shuffle();
            $likers = $shuffledUsers->take($likeCount);
            
            foreach ($likers as $user) {
                $blog->likedBy()->attach($user->id);
            }
            $blog->update(['likes_count' => $likeCount]);

            // Add Comments (5-20 per post)
            $commentCount = rand(5, 20);
            for ($c = 0; $c < $commentCount; $c++) {
                $blog->comments()->create([
                    'user_id' => $shuffledUsers->random()->id,
                    'content' => fake()->sentence(rand(10, 30)),
                    'status' => 'approved',
                ]);
            }
        }
    }
}
