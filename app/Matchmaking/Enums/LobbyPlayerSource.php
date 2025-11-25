<?php

namespace App\Matchmaking\Enums;

enum LobbyPlayerSource: string
{
    case HOST = 'host';
    case INVITED = 'invited';
    case PUBLIC_JOIN = 'public_join';
    case QUEUE_MATCHED = 'queue_matched';
}
