<?php

namespace App\Providers;

use App\Events\MatchFinished;
use App\Listeners\AdvanceKnockoutWinner;
use App\Listeners\RefreshStandings;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        Gate::before(function (User $user) {
            return $user->isAdmin() ? true : null;
        });

        Event::listen(MatchFinished::class, AdvanceKnockoutWinner::class);
        Event::listen(MatchFinished::class, RefreshStandings::class);
    }
}
