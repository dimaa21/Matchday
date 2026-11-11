<?php

namespace App\Http\Controllers\Public;

use App\Enums\MatchStatus;
use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\MatchEvent;
use App\Models\Player;
use App\Services\StatisticsService;
use App\Services\SuspensionService;
use Inertia\Inertia;
use Inertia\Response;

class PlayerController extends Controller
{
    public function show(Player $player, StatisticsService $statistics): Response
    {
        $player->load('team');

        $events = MatchEvent::query()
            ->with(['game.tournament', 'game.homeTeam', 'game.awayTeam'])
            ->where('player_id', $player->id)
            ->latest('id')
            ->limit(20)
            ->get()
            ->map(fn (MatchEvent $event) => [
                'id' => $event->id,
                'type' => $event->type->value,
                'label' => $event->type->label(),
                'minute' => $event->minute,
                'match_id' => $event->match_id,
                'tournament' => $event->game?->tournament?->name,
                'opponent' => $event->game?->home_team_id === $player->team_id
                    ? $event->game?->awayTeam?->name
                    : $event->game?->homeTeam?->name,
            ]);

        $next = Game::query()
            ->where('status', MatchStatus::Scheduled)
            ->where('is_bye', false)
            ->where(fn ($query) => $query->where('home_team_id', $player->team_id)->orWhere('away_team_id', $player->team_id))
            ->orderBy('round')
            ->orderBy('kickoff_at')
            ->first();

        return Inertia::render('Players/Show', [
            'player' => [
                'id' => $player->id,
                'name' => $player->name,
                'number' => $player->squad_number,
                'position' => $player->position->label(),
                'photo_url' => $player->photo_url,
                'initials' => $player->initials(),
                'team' => $player->team ? [
                    'name' => $player->team->name,
                    'slug' => $player->team->slug,
                    'primary_color' => $player->team->primary_color,
                ] : null,
                'stats' => $statistics->forPlayer($player),
                'suspended_next' => $next ? app(SuspensionService::class)->isSuspended($player, $next) : false,
            ],
            'events' => $events,
        ]);
    }
}
