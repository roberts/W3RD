<?php

return [

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
