<?php

namespace Database\Seeders;

use App\Models\Title\Title;
use Illuminate\Database\Seeder;

class TitleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $titles = [
            [
                'slug' => 'validate-four',
                'name' => 'Validate Four',
                'max_players' => 2,
            ],
            [
                'slug' => 'checkers',
                'name' => 'Checkers',
                'max_players' => 2,
            ],
            [
                'slug' => 'hearts',
                'name' => 'Hearts',
                'max_players' => 4,
            ],
            [
                'slug' => 'spades',
                'name' => 'Spades',
                'max_players' => 4,
            ],
        ];

        foreach ($titles as $title) {
            Title::updateOrCreate(
                ['slug' => $title['slug']],
                $title
            );
        }
    }
}
