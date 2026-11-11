<?php

namespace App\Http\Controllers\Public;

use App\Enums\MatchStatus;
use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\Player;
use App\Models\Team;
use App\Services\GamePresenter;
use App\Services\StatisticsService;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    public function show(Team $team, StatisticsService $statistics, GamePresenter $games): Response
    {
        $players = $team->players()->get()->map(function (Player $player) use ($statistics) {
            return [
                'id' => $player->id,
                'name' => $player->name,
                'number' => $player->squad_number,
                'position' => $player->position->label(),
                'position_short' => $player->position->short(),
                'photo_url' => $player->photo_url,
                'initials' => $player->initials(),
                'stats' => $statistics->forPlayer($player),
            ];
        });

        $recent = Game::query()
            ->with(['homeTeam', 'awayTeam', 'tournament'])
            ->where('is_bye', false)
            ->where('status', MatchStatus::Finished)
            ->where(fn ($query) => $query->where('home_team_id', $team->id)->orWhere('away_team_id', $team->id))
            ->orderByDesc('kickoff_at')
            ->limit(8)
            ->get()
            ->map(fn (Game $game) => $games->summary($game));

        return Inertia::render('Teams/Show', [
            'team' => [
                'name' => $team->name,
                'slug' => $team->slug,
                'city' => $team->city,
                'initials' => $team->initials(),
                'primary_color' => $team->primary_color,
                'secondary_color' => $team->secondary_color,
                'logo_url' => $team->logo_url,
                'stats' => $statistics->forTeam($team->id),
            ],
            'players' => $players,
            'recent' => $recent,
        ]);
    }
}
