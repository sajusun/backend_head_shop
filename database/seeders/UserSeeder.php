<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Admin User',
                'email' => 'admin@herdshop.com',
                'password' => 'admin123',
                'role' => 'admin',
                'phone' => '+8801700000001',
            ],
            [
                'name' => 'Test User',
                'email' => 'user@herdshop.com',
                'password' => 'user123',
                'role' => 'user',
                'phone' => '+8801700000002',
            ],
            [
                'name' => 'Ayesha Rahman',
                'email' => 'ayesha@example.com',
                'password' => 'password',
                'role' => 'user',
                'phone' => '+8801700000003',
            ],
            [
                'name' => 'Tanvir Hasan',
                'email' => 'tanvir@example.com',
                'password' => 'password',
                'role' => 'user',
                'phone' => '+8801700000004',
            ],
            [
                'name' => 'Nusrat Jahan',
                'email' => 'nusrat@example.com',
                'password' => 'password',
                'role' => 'user',
                'phone' => '+8801700000005',
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => Hash::make($user['password']),
                    'role' => $user['role'],
                    'phone' => $user['phone'],
                    'email_verified_at' => now(),
                ]
            );
        }

        // Create 60 additional random users
        User::factory()->count(60)->create();
    }
}
