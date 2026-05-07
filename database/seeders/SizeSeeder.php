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
            ['name' => 'Extra Small', 'short' => 'XS'],
            ['name' => 'Small', 'short' => 'S'],
            ['name' => 'Medium', 'short' => 'M'],
            ['name' => 'Large', 'short' => 'L'],
            ['name' => 'Extra Large', 'short' => 'XL'],
            ['name' => 'Double Extra Large', 'short' => 'XXL'],
        ];

        foreach ($sizes as $size) {
            Size::updateOrCreate(
                ['slug' => Str::slug($size['name'])],
                [
                    'name' => $size['name'],
                    'short' => $size['short'],
                    'status' => 'active',
                ]
            );
        }
    }
}
