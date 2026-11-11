<?php

namespace App\Listeners;

use App\Events\MatchFinished;
use App\Services\StandingsService;
use Illuminate\Contracts\Queue\ShouldQueue;

class RefreshStandings implements ShouldQueue
{
    public function __construct(private readonly StandingsService $standings) {}

    public function handle(MatchFinished $event): void
    {
        $tournament = $event->game->tournament;

        if ($tournament === null) {
            return;
        }

        $this->standings->invalidate($tournament);
        $this->standings->cached($tournament);
    }
}
