<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\Tournament;
use App\Services\GamePresenter;
use App\Services\StandingsService;
use App\Services\StatisticsService;
use Inertia\Inertia;
use Inertia\Response;

class TournamentController extends Controller
{
    public function show(
        Tournament $tournament,
        StandingsService $standings,
        StatisticsService $statistics,
        GamePresenter $games,
    ): Response {
        $fixtures = $tournament->games()
            ->with(['homeTeam', 'awayTeam'])
            ->orderBy('round')
            ->orderBy('kickoff_at')
            ->orderBy('id')
            ->get()
            ->map(fn (Game $game) => $games->summary($game))
            ->groupBy(fn (array $game) => ($game['group_name'] ?: 'main').':'.$game['round'])
            ->map(function ($roundGames) {
                $first = $roundGames->first();

                return [
                    'round' => $first['round'],
                    'group_name' => $first['group_name'],
                    'matches' => $roundGames->values(),
                ];
            })
            ->values();

        return Inertia::render('Tournaments/Show', [
            'tournament' => [
                'name' => $tournament->name,
                'slug' => $tournament->slug,
                'season' => $tournament->season,
                'format' => $tournament->format->label(),
                'format_value' => $tournament->format->value,
                'status' => $tournament->status->value,
                'status_label' => $tournament->status->label(),
                'points_for_win' => $tournament->points_for_win,
                'points_for_draw' => $tournament->points_for_draw,
            ],
            'tables' => $standings->cached($tournament),
            'fixtures' => $fixtures,
            'scorers' => $statistics->topScorers($tournament, 15),
        ]);
    }
}
