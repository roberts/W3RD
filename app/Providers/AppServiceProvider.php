<?php

namespace App\Providers;

use App\Events\RematchAccepted;
use App\Events\RematchDeclined;
use App\Events\RematchExpired;
use App\Events\RematchRequested;
use App\Listeners\SendRematchAcceptedNotification;
use App\Listeners\SendRematchDeclinedNotification;
use App\Listeners\SendRematchExpiredNotification;
use App\Listeners\SendRematchRequestNotification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register rematch event listeners
        Event::listen(RematchRequested::class, SendRematchRequestNotification::class);
        Event::listen(RematchAccepted::class, SendRematchAcceptedNotification::class);
        Event::listen(RematchDeclined::class, SendRematchDeclinedNotification::class);
        Event::listen(RematchExpired::class, SendRematchExpiredNotification::class);
    }
}
