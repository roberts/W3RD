<?php

namespace App\Providers;

use App\Events\GameCompleted;
use App\Events\ProposalAccepted;
use App\Events\ProposalCancelled;
use App\Events\ProposalCreated;
use App\Events\ProposalDeclined;
use App\Events\ProposalExpired;
use App\GameEngine\GameEngine;
use App\Listeners\SendProposalAcceptedAlert;
use App\Listeners\SendProposalCancelledAlert;
use App\Listeners\SendProposalCreatedAlert;
use App\Listeners\SendProposalDeclinedAlert;
use App\Listeners\SendProposalExpiredAlert;
use App\Listeners\SetAgentCooldownAfterGame;
use App\Listeners\SetPlayerActivityAfterGame;
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
