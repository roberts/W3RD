<?php

namespace App\Enums;

enum FeedbackType: string
{
    case BUG = 'bug';
    case REPORT = 'report';
    case FEATURE = 'feature';
    case ACCOUNT = 'account';
    case CLIENT = 'client';
    case BUSINESS = 'business';
}
