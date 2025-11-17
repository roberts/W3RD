<?php

use App\Jobs\ExpireRematchRequests;
use App\Jobs\ProcessQuickplayQueue;
use App\Jobs\ProcessScheduledLobbies;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule the quickplay queue processor
Schedule::job(new ProcessQuickplayQueue)->everyTenSeconds();

// Schedule the scheduled lobbies processor
Schedule::job(new ProcessScheduledLobbies)->everyMinute();

// Schedule the rematch request expiration processor
Schedule::job(new ExpireRematchRequests)->everyMinute();
