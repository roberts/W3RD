<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Game\Game;

class GameSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $games = [
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

        foreach ($games as $game) {
            Game::updateOrCreate(
                ['slug' => $game['slug']],
                $game
            );
        }
    }
}
