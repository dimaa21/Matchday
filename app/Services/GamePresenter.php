<?php

namespace App\Services;

use App\Models\Game;
use App\Models\MatchEvent;
use App\Models\Team;

class GamePresenter
{
    /**
     * @return array<string, mixed>
     */
    public function summary(Game $game): array
    {
        $game->loadMissing(['homeTeam', 'awayTeam', 'tournament']);

        return [
            'id' => $game->id,
            'round' => $game->round,
            'group_name' => $game->group_name,
            'status' => $game->status->value,
            'status_label' => $game->status->label(),
            'kickoff_at' => $game->kickoff_at?->toIso8601String(),
            'kickoff_label' => $game->kickoff_at?->format('d.m.Y H:i'),
            'venue' => $game->venue,
            'home_score' => $game->home_score,
            'away_score' => $game->away_score,
            'is_bye' => $game->is_bye,
            'home_team' => $this->team($game->homeTeam),
            'away_team' => $this->team($game->awayTeam),
            'tournament' => $game->tournament ? [
                'name' => $game->tournament->name,
                'slug' => $game->tournament->slug,
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function detail(Game $game): array
    {
        $game->loadMissing([
            'tournament',
            'homeTeam',
            'awayTeam',
            'referee',
            'events.player',
            'events.assist',
            'events.replacement',
            'squads.player',
        ]);

        return [
            ...$this->summary($game),
            'referee' => $game->referee?->name,
            'events' => $game->events->map(fn (MatchEvent $event) => [
                'id' => $event->id,
                'type' => $event->type->value,
                'label' => $event->type->label(),
                'minute' => $event->minute,
                'player_id' => $event->player_id,
                'player' => $event->player?->name,
                'team_id' => $event->player?->team_id,
                'assist' => $event->assist?->name,
                'secondary' => $event->replacement?->name,
            ])->values()->all(),
            'lineups' => [
                'home' => $this->lineup($game, $game->home_team_id),
                'away' => $this->lineup($game, $game->away_team_id),
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function team(?Team $team): ?array
    {
        if ($team === null) {
            return null;
        }

        return [
            'id' => $team->id,
            'name' => $team->name,
            'slug' => $team->slug,
            'city' => $team->city,
            'initials' => $team->initials(),
            'primary_color' => $team->primary_color,
            'secondary_color' => $team->secondary_color,
            'logo_url' => $team->logo_url,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function lineup(Game $game, ?int $teamId): array
    {
        if ($teamId === null) {
            return [];
        }

        return $game->squads
            ->where('team_id', $teamId)
            ->sortBy(fn ($squad) => sprintf('%d-%03d', $squad->is_starter ? 0 : 1, $squad->player?->squad_number ?? 99))
            ->map(fn ($squad) => [
                'player_id' => $squad->player_id,
                'name' => $squad->player?->name,
                'number' => $squad->player?->squad_number,
                'position' => $squad->player?->position?->short(),
                'is_starter' => $squad->is_starter,
            ])
            ->values()
            ->all();
    }
}
