<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MatchStatus;
use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\Tournament;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $finished = Game::query()->where('status', MatchStatus::Finished)->where('is_bye', false);
        $matchCount = (clone $finished)->count();
        $goals = (int) (clone $finished)->sum(DB::raw('home_score + away_score'));

        $rounds = Game::query()
            ->where('status', MatchStatus::Finished)
            ->where('is_bye', false)
            ->select('tournament_id', 'round', DB::raw('SUM(home_score + away_score) as goals'))
            ->groupBy('tournament_id', 'round')
            ->orderByDesc('goals')
            ->limit(5)
            ->get();

        $names = Tournament::query()->whereIn('id', $rounds->pluck('tournament_id'))->pluck('name', 'id');

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'matches' => $matchCount,
                'goals' => $goals,
                'average' => $matchCount > 0 ? round($goals / $matchCount, 2) : 0,
                'live' => Game::query()->where('status', MatchStatus::Live)->count(),
            ],
            'rounds' => $rounds->map(fn ($row) => [
                'tournament' => $names[$row->tournament_id] ?? 'Tournament',
                'round' => $row->round,
                'goals' => (int) $row->goals,
            ]),
        ]);
    }
}
