<?php

namespace App\Providers;

use App\Events\RematchAccepted;
use App\Events\RematchDeclined;
use App\Events\RematchExpired;
use App\Events\RematchRequested;
use App\Listeners\SendRematchAcceptedAlert;
use App\Listeners\SendRematchDeclinedAlert;
use App\Listeners\SendRematchExpiredAlert;
use App\Listeners\SendRematchRequestAlert;
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
        Event::listen(RematchRequested::class, SendRematchRequestAlert::class);
        Event::listen(RematchAccepted::class, SendRematchAcceptedAlert::class);
        Event::listen(RematchDeclined::class, SendRematchDeclinedAlert::class);
        Event::listen(RematchExpired::class, SendRematchExpiredAlert::class);
    }
}
