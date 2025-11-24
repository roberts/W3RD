<?php

namespace App\Enums;

enum FeedbackStatus: string
{
    case PENDING = 'pending';
    case REVIEWING = 'reviewing';
    case RESOLVED = 'resolved';
    case REJECTED = 'rejected';
}
