<?php

namespace Database\Seeders;

use App\Models\Color;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ColorSeeder extends Seeder
{
    public function run(): void
    {
        $colors = [
            ['name' => 'Black', 'code' => '#000000'],
            ['name' => 'White', 'code' => '#FFFFFF'],
            ['name' => 'Red', 'code' => '#FF0000'],
            ['name' => 'Blue', 'code' => '#0000FF'],
            ['name' => 'Green', 'code' => '#008000'],
            ['name' => 'Yellow', 'code' => '#FFFF00'],
            ['name' => 'Orange', 'code' => '#FFA500'],
            ['name' => 'Purple', 'code' => '#800080'],
            ['name' => 'Pink', 'code' => '#FFC0CB'],
            ['name' => 'Gray', 'code' => '#808080'],
        ];

        foreach ($colors as $color) {
            Color::updateOrCreate(
                ['slug' => Str::slug($color['name'])],
                [
                    'name' => $color['name'],
                    'hex_code' => $color['code'],
                    'status' => 'active',
                ]
            );
        }
    }
}
