<?php

namespace App\Http\Controllers\Public;

use App\Enums\MatchStatus;
use App\Enums\TournamentStatus;
use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\Tournament;
use App\Services\GamePresenter;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(GamePresenter $games): Response
    {
        $tournaments = Tournament::query()
            ->where('status', '!=', TournamentStatus::Draft)
            ->withCount('teams')
            ->latest()
            ->get();

        $live = Game::query()
            ->with(['homeTeam', 'awayTeam', 'tournament'])
            ->where('status', MatchStatus::Live)
            ->latest('kickoff_at')
            ->limit(6)
            ->get();

        return Inertia::render('Home', [
            'tournaments' => $tournaments->map(fn (Tournament $tournament) => [
                'name' => $tournament->name,
                'slug' => $tournament->slug,
                'season' => $tournament->season,
                'format' => $tournament->format->label(),
                'status' => $tournament->status->value,
                'status_label' => $tournament->status->label(),
                'teams_count' => $tournament->teams_count,
            ]),
            'live' => $live->map(fn (Game $game) => $games->summary($game))->values(),
        ]);
    }
}
