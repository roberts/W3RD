<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Gamification\Badge;

class BadgeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $badges = [
            [
                'slug' => 'first-win',
                'name' => 'First Victory',
                'image_id' => null,
                'condition_json' => ['wins' => 1],
            ],
            [
                'slug' => 'ten-wins',
                'name' => 'Rising Star',
                'image_id' => null,
                'condition_json' => ['wins' => 10],
            ],
            [
                'slug' => 'hundred-wins',
                'name' => 'Century Club',
                'image_id' => null,
                'condition_json' => ['wins' => 100],
            ],
            [
                'slug' => 'streak-master',
                'name' => 'Streak Master',
                'image_id' => null,
                'condition_json' => ['win_streak' => 5],
            ],
        ];

        foreach ($badges as $badge) {
            Badge::updateOrCreate(
                ['slug' => $badge['slug']],
                $badge
            );
        }
    }
}
