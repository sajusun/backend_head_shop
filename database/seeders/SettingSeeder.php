<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // General Settings
            ['key' => 'site_name', 'value' => 'HERD SHOP', 'group' => 'general', 'type' => 'text'],
            ['key' => 'site_title', 'value' => 'HERD SHOP | Premium Lifestyle Store', 'group' => 'general', 'type' => 'text'],
            ['key' => 'site_email', 'value' => 'hello@herdshop.com', 'group' => 'general', 'type' => 'text'],
            ['key' => 'site_phone', 'value' => '+1 (555) 123-4567', 'group' => 'general', 'type' => 'text'],
            ['key' => 'site_address', 'value' => '123 Premium St, Design District, NY 10001', 'group' => 'general', 'type' => 'text'],
            
            // SEO Settings
            ['key' => 'seo_description', 'value' => 'HERD SHOP offers a curated collection of premium lifestyle products, from tech accessories to home essentials.', 'group' => 'seo', 'type' => 'text'],
            ['key' => 'seo_keywords', 'value' => 'premium, lifestyle, tech accessories, minimalist design, herd shop', 'group' => 'seo', 'type' => 'text'],
            
            // Static Pages
            ['key' => 'about_page_content', 'value' => '<h1>About HERD SHOP</h1><p>Founded in 2024, HERD SHOP was born out of a passion for minimalist design and premium craftsmanship. We believe that the objects you surround yourself with should be as beautiful as they are functional.</p><p>Our mission is to curate a collection of lifestyle essentials that elevate your daily routine, focusing on quality materials and timeless aesthetics.</p>', 'group' => 'pages', 'type' => 'html'],
            
            ['key' => 'privacy_policy_content', 'value' => '<h1>Privacy Policy</h1><p>Your privacy is important to us. It is HERD SHOP\'s policy to respect your privacy regarding any information we may collect from you across our website.</p><p>We only ask for personal information when we truly need it to provide a service to you. We collect it by fair and lawful means, with your knowledge and consent.</p>', 'group' => 'pages', 'type' => 'html'],
            
            ['key' => 'terms_of_use_content', 'value' => '<h1>Terms of Use</h1><p>By accessing the website at herdshop.com, you are agreeing to be bound by these terms of service, all applicable laws and regulations, and agree that you are responsible for compliance with any applicable local laws.</p>', 'group' => 'pages', 'type' => 'html'],
            
            ['key' => 'shipping_policy_content', 'value' => '<h1>Shipping Policy</h1><p>We offer free express shipping on all orders over $100. Most orders are processed within 24 hours and delivered within 3-5 business days.</p>', 'group' => 'pages', 'type' => 'html'],
            
            ['key' => 'returns_policy_content', 'value' => '<h1>Returns & Exchanges</h1><p>If you are not 100% satisfied with your purchase, you can return the product and get a full refund or exchange the product for another one, be it similar or not.</p>', 'group' => 'pages', 'type' => 'html'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
