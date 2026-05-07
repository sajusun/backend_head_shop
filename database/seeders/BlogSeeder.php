<?php

namespace Database\Seeders;

use App\Models\Blog;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BlogSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();
        if (!$admin) {
            $admin = User::factory()->create(['role' => 'admin', 'name' => 'Admin User']);
        }

        $categories = [
            'Tech Trends',
            'Lifestyle',
            'Fashion Tips',
            'Health & Wellness',
            'Product Reviews',
            'Company News',
        ];

        $blogCategories = [];
        foreach ($categories as $catName) {
            $blogCategories[] = Category::updateOrCreate(
                ['slug' => Str::slug($catName)],
                [
                    'name' => $catName,
                    'type' => 'blog',
                    'status' => 'active',
                ]
            );
        }

        $titles = [
            '10 Ways to Improve Your Daily Routine',
            'The Future of Sustainable Fashion',
            'How to Choose the Perfect Gadget for Your Home',
            'Mastering the Art of Minimalist Living',
            'Top 5 Destinations for Your Summer Vacation',
            'Understanding the Basics of Modern Interior Design',
            'The Ultimate Guide to Healthy Eating',
            'Why Quality Matters in Every Purchase',
            'The Evolution of E-commerce in 2026',
            'Building a Productive Workspace at Home',
            'Skincare Secrets for a Radiant Glow',
            'How to Style Your Wardrobe for Every Occasion',
            'The Impact of Technology on Modern Sports',
            'Essential Tips for First-Time Home Buyers',
            'The Joy of Slow Living in a Fast-Paced World',
            'Creative Ways to Use Your Old Electronics',
            'A Deep Dive into Organic Fabrics',
            'Fitness Motivation: How to Stay Consistent',
            'The Best Books to Read This Year',
            'Transforming Your Backyard into an Oasis',
            'Smart Home Devices You Actually Need',
            'The Rise of Personalized Shopping Experiences',
            'Eco-Friendly Gift Ideas for Your Loved Ones',
            'Navigating the World of Online Reviews',
            'The Importance of Mental Health Awareness',
            'Exploring the Hidden Gems of Your City',
            'How to Prepare Your Car for a Long Road Trip',
            'The Art of Gifting: Making Every Moment Special',
            'Latest Trends in Wearable Technology',
            'Finding Balance in Your Work-Life Journey',
        ];

        foreach ($titles as $index => $title) {
            $slug = Str::slug($title);
            
            // Check if slug exists
            $existingCount = Blog::where('slug', 'like', $slug . '%')->count();
            if ($existingCount > 0) {
                $slug = $slug . '-' . ($index + 1);
            }

            Blog::create([
                'author_id' => $admin->id,
                'category_id' => $blogCategories[array_rand($blogCategories)]->id,
                'title' => $title,
                'slug' => $slug,
                'excerpt' => 'This is a brief overview of the article titled "' . $title . '". Learn more about the latest trends and insights in this comprehensive guide.',
                'content' => '<h3>Introduction</h3><p>Welcome to our deep dive into ' . $title . '. In today\'s world, staying informed is more important than ever.</p><p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p><h4>Why It Matters</h4><p>Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p><p>Curabitur pretium tincidunt lacus. Nulla gravida orci a odio. Nullam varius, turpis et commodo pharetra, est eros bibendum elit, nec luctus magna felis sollicitudin mauris. Integer in mauris eu nibh euismod gravida. Duis ac tellus et risus vulputate vehicula. Donec lobortis risus a elit. Etiam tempor. Ut ullamcorper, ligula eu tempor congue, eros est euismod turpis, id tincidunt sapien risus a quam. Maecenas fermentum consequat mi. Donec fermentum. Pellentesque malesuada nulla a mi. Duis sapien sem, aliquet nec, commodo eget, consequat quis, neque. Aliquam faucibus, elit ut dictum aliquet, felis nisl adipiscing sapien, sed malesuada diam lacus eget erat. Cras mollis scelerisque nunc. Donec vehicula cursus purus.</p><blockquote>"Knowledge is power, but sharing it is even more powerful."</blockquote><p>In conclusion, ' . $title . ' is a topic that continues to evolve. We hope this guide provides you with the clarity you need to move forward.</p>',
                'featured_image' => 'https://picsum.photos/seed/' . $slug . '/800/600',
                'status' => $index < 25 ? 'published' : 'draft',
                'likes_count' => rand(10, 500),
                'views_count' => rand(100, 2000),
                'published_at' => $index < 25 ? now()->subDays(rand(1, 30)) : null,
            ]);
        }
    }
}
