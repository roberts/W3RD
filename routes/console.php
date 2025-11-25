<?php

use App\Jobs\ExpireProposals;
use App\Jobs\FillLobbiesFromQueue;
use App\Jobs\ProcessMatchmakingQueue;
use App\Jobs\ProcessScheduledLobbies;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule the matchmaking queue processor (priority: runs first)
Schedule::job(new ProcessMatchmakingQueue)->everyTenSeconds();

// Schedule filling lobbies from queue (runs after queue-to-queue matching)
Schedule::job(new FillLobbiesFromQueue)->everyTenSeconds();

// Schedule the scheduled lobbies processor
Schedule::job(new ProcessScheduledLobbies)->everyMinute();

// Schedule the rematch request expiration processor
Schedule::job(new ExpireProposals)->everyMinute();
