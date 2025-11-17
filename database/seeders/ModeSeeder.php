<?php

namespace Database\Seeders;

use App\Models\Game\Mode;
use Illuminate\Database\Seeder;

class ModeSeeder extends Seeder
{
    /**
     * Seed the game modes.
     */
    public function run(): void
    {
        $modes = [
            // Validate Four modes
            [
                'title_slug' => 'validate-four',
                'slug' => 'standard',
                'name' => 'Standard (7x6)',
                'is_active' => true,
            ],
            [
                'title_slug' => 'validate-four',
                'slug' => 'pop_out',
                'name' => 'Pop Out',
                'is_active' => true,
            ],
            [
                'title_slug' => 'validate-four',
                'slug' => 'eight_by_seven',
                'name' => '8x7 Board',
                'is_active' => true,
            ],
            [
                'title_slug' => 'validate-four',
                'slug' => 'nine_by_six',
                'name' => '9x6 Board',
                'is_active' => true,
            ],
            [
                'title_slug' => 'validate-four',
                'slug' => 'five',
                'name' => 'Connect Five',
                'is_active' => true,
            ],
        ];

        foreach ($modes as $mode) {
            Mode::updateOrCreate(
                [
                    'title_slug' => $mode['title_slug'],
                    'slug' => $mode['slug'],
                ],
                $mode
            );
        }

        $this->command->info('Game modes seeded successfully.');
    }
}
