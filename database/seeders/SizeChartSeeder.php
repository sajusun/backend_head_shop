<?php

namespace Database\Seeders;

use App\Models\SizeChart;
use Illuminate\Database\Seeder;

class SizeChartSeeder extends Seeder
{
    public function run(): void
    {
        $charts = [
            [
                'name' => "Men's T-Shirt Standard Fit Guide",
                'department' => 'men',
                'category_type' => 'topwear',
                'unit' => 'inch',
                'columns' => ['Size', 'Chest', 'Length', 'Shoulder', 'Sleeve'],
                'rows' => [
                    ['S', '38', '27', '17.5', '8.0'],
                    ['M', '40', '28', '18.5', '8.5'],
                    ['L', '42', '29', '19.5', '9.0'],
                    ['XL', '44', '30', '20.5', '9.5'],
                    ['XXL', '46', '31', '21.5', '10.0'],
                ],
                'how_to_measure' => "Chest: Measure around the fullest part of your chest, keeping tape horizontal under arms. Length: Measure from the highest point of the shoulder down to the bottom hem.",
            ],
            [
                'name' => "Men's Oversized Drop-Shoulder Guide",
                'department' => 'men',
                'category_type' => 'topwear',
                'unit' => 'inch',
                'columns' => ['Size', 'Chest', 'Length', 'Shoulder Drop', 'Sleeve'],
                'rows' => [
                    ['S', '42', '28.5', '21.0', '9.0'],
                    ['M', '44', '29.5', '22.0', '9.5'],
                    ['L', '46', '30.5', '23.0', '10.0'],
                    ['XL', '48', '31.5', '24.0', '10.5'],
                ],
                'how_to_measure' => "Designed for a relaxed, baggy aesthetic. Choose your true size for the intended oversized look.",
            ],
            [
                'name' => "Women's Kurti & Top Size Guide",
                'department' => 'women',
                'category_type' => 'topwear',
                'unit' => 'inch',
                'columns' => ['Size', 'Bust', 'Waist', 'Hip', 'Length'],
                'rows' => [
                    ['S', '36', '32', '38', '40'],
                    ['M', '38', '34', '40', '42'],
                    ['L', '40', '36', '42', '42'],
                    ['XL', '42', '38', '44', '44'],
                    ['XXL', '44', '40', '46', '44'],
                ],
                'how_to_measure' => "Bust: Measure fullest part of bust. Waist: Measure narrowest part of natural waistline.",
            ],
            [
                'name' => "Kids & Toddler Clothing Size Guide",
                'department' => 'kids',
                'category_type' => 'unisex',
                'unit' => 'inch',
                'columns' => ['Age Group', 'Chest', 'Height (cm)', 'Waist'],
                'rows' => [
                    ['1-2Y', '21', '86-92', '20'],
                    ['3-4Y', '23', '98-104', '21.5'],
                    ['5-6Y', '25', '110-116', '22.5'],
                    ['7-8Y', '27', '122-128', '24'],
                    ['9-10Y', '29', '134-140', '25.5'],
                ],
                'how_to_measure' => "Height: Measure from top of head to floor without shoes.",
            ],
        ];

        foreach ($charts as $chart) {
            SizeChart::updateOrCreate(
                ['name' => $chart['name']],
                $chart
            );
        }
    }
}
