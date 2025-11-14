<?php

namespace Database\Seeders;

use App\Models\Content\Avatar;
use Illuminate\Database\Seeder;

class AvatarSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $avatars = [
            ['name' => 'Default 1', 'image_id' => null, 'type' => 'free'],
            ['name' => 'Default 2', 'image_id' => null, 'type' => 'free'],
            ['name' => 'Default 3', 'image_id' => null, 'type' => 'free'],
            ['name' => 'Default 4', 'image_id' => null, 'type' => 'free'],
            ['name' => 'Default 5', 'image_id' => null, 'type' => 'free'],
        ];

        foreach ($avatars as $avatar) {
            Avatar::updateOrCreate(
                ['name' => $avatar['name']],
                $avatar
            );
        }
    }
}
