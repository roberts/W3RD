<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Game Configuration
    |--------------------------------------------------------------------------
    |
    | This file maps game titles to their available modes and corresponding
    | strategy classes. Each game can have multiple modes, and each mode
    | is mapped to a concrete implementation of the GameTitleContract interface.
    |
    */

    'validate_four' => [
        'name' => 'Validate Four',
        'description' => 'A strategic disc-dropping game where players compete to connect discs in a row',
        'modes' => [
            'standard' => [
                'name' => 'Standard Mode',
                'description' => 'Classic 7x6 Connect Four gameplay',
                'class' => \App\Games\ValidateFour\Modes\StandardMode::class,
            ],
            'pop_out' => [
                'name' => 'Pop Out Mode',
                'description' => 'Play with the ability to pop out your own discs from the bottom',
                'class' => \App\Games\ValidateFour\Modes\PopOutMode::class,
            ],
            'eight_by_seven' => [
                'name' => '8x7 Mode',
                'description' => 'Connect 4 on a larger 8x7 grid',
                'class' => \App\Games\ValidateFour\Modes\EightBySevenMode::class,
            ],
            'nine_by_six' => [
                'name' => '9x6 Mode',
                'description' => 'Connect 4 on a wider 9x6 grid',
                'class' => \App\Games\ValidateFour\Modes\NineBySixMode::class,
            ],
            'five' => [
                'name' => 'Five Mode',
                'description' => 'Connect 5 discs on a standard grid',
                'class' => \App\Games\ValidateFour\Modes\FiveMode::class,
            ],
        ],
    ],
];
