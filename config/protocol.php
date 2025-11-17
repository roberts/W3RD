<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Game Titles
    |--------------------------------------------------------------------------
    |
    | This array defines all available game titles in the protocol platform.
    | Each game has a key (used in URLs/API), name (display), and description.
    |
    */

    'game_titles' => [
        [
            'key' => 'validate-four',
            'name' => 'Validate Four',
            'description' => 'Classic connect four game where players compete to align four pieces in a row.',
        ],
        // Add more game titles here as they become available
    ],

    /*
    |--------------------------------------------------------------------------
    | Subscription Plans
    |--------------------------------------------------------------------------
    |
    | Define available subscription plans and their features.
    |
    */

    'subscription_plans' => [
        [
            'id' => 'basic',
            'name' => 'Basic',
            'price' => 2,
            'features' => [
                'Play all games',
                'No ads',
                'Basic statistics',
            ],
        ],
        [
            'id' => 'premium',
            'name' => 'Premium',
            'price' => 5,
            'stripe_price_id' => env('STRIPE_PREMIUM_PRICE_ID'),
            'features' => [
                'Play all games',
                'Advanced statistics',
                'Priority matchmaking',
                'No ads',
                'Custom avatars',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rematch Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the rematch feature.
    |
    */

    'rematch' => [
        'expiration_minutes' => 5, // Rematch requests expire after 5 minutes
    ],

];
