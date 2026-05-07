<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Admin User
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@herdshop.com',
            'password' => \Illuminate\Support\Facades\Hash::make('admin123'),
            'role' => 'admin',
        ]);

        // Create Regular User
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'user@herdshop.com',
            'password' => \Illuminate\Support\Facades\Hash::make('user123'),
            'role' => 'user',
        ]);
    }
}
