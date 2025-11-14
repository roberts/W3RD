<?php

namespace Database\Seeders;

use App\Models\Auth\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed base data
        $this->call([
            GameSeeder::class,
            AvatarSeeder::class,
            ClientSeeder::class,
            BadgeSeeder::class,
        ]);

        // Create test user
        User::factory()->create([
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
        ]);
    }
}
