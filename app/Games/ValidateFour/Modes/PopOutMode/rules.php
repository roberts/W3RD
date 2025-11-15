<?php

return [
    'mode' => 'pop_out',
    'description' => 'Connect 4 discs in a row with the ability to pop out your own discs from the bottom',
    'available_actions' => [
        'drop_disc',
        'pop_out',
    ],
    'action_rules' => [
        'pop_out' => [
            'description' => 'Remove your own disc from the bottom of a column, causing all discs above to fall down',
            'validation' => [
                'The bottom disc in the column must belong to the current player',
                'The column must not be empty',
            ],
        ],
    ],
];
