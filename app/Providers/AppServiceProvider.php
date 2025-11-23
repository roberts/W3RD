<?php

namespace App\Providers;

use App\GameEngine\Events\GameCompleted;
use App\GameEngine\GameEngine;
use App\Listeners\SendProposalAcceptedAlert;
use App\Listeners\SendProposalCancelledAlert;
use App\Listeners\SendProposalCreatedAlert;
use App\Listeners\SendProposalDeclinedAlert;
use App\Listeners\SendProposalExpiredAlert;
use App\Listeners\SetAgentCooldownAfterGame;
use App\Listeners\SetPlayerActivityAfterGame;
use App\Matchmaking\Events\ProposalAccepted;
use App\Matchmaking\Events\ProposalCancelled;
use App\Matchmaking\Events\ProposalCreated;
use App\Matchmaking\Events\ProposalDeclined;
use App\Matchmaking\Events\ProposalExpired;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(GameEngine::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register rematch event listeners
        Event::listen(ProposalCreated::class, SendProposalCreatedAlert::class);
        Event::listen(ProposalAccepted::class, SendProposalAcceptedAlert::class);
        Event::listen(ProposalDeclined::class, SendProposalDeclinedAlert::class);
        Event::listen(ProposalExpired::class, SendProposalExpiredAlert::class);
        Event::listen(ProposalCancelled::class, SendProposalCancelledAlert::class);

        // Register game completion listeners
        Event::listen(GameCompleted::class, SetAgentCooldownAfterGame::class);
        Event::listen(GameCompleted::class, SetPlayerActivityAfterGame::class);
    }
}
