<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Content\Avatar;

class AvatarSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $avatars = [
            ['name' => 'Default 1', 'image_url' => '/avatars/default-1.png', 'type' => 'free'],
            ['name' => 'Default 2', 'image_url' => '/avatars/default-2.png', 'type' => 'free'],
            ['name' => 'Default 3', 'image_url' => '/avatars/default-3.png', 'type' => 'free'],
            ['name' => 'Default 4', 'image_url' => '/avatars/default-4.png', 'type' => 'free'],
            ['name' => 'Default 5', 'image_url' => '/avatars/default-5.png', 'type' => 'free'],
        ];

        foreach ($avatars as $avatar) {
            Avatar::updateOrCreate(
                ['name' => $avatar['name']],
                $avatar
            );
        }
    }
}
