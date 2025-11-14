<?php

namespace App\Enums;

enum Platform: string
{
    case WEB = 'web';
    case IOS = 'ios';
    case ANDROID = 'android';
    case ELECTRON = 'electron';
    case CLI = 'cli';
}
