<?php

namespace App\Services;

use App\Models\Game;

class MatchPayload
{
    /**
     * @return array<string, mixed>
     */
    public function broadcast(Game $game): array
    {
        $game->loadMissing(['homeTeam', 'awayTeam', 'events.player', 'events.assist', 'events.replacement']);

        return [
            'id' => $game->id,
            'status' => $game->status->value,
            'home_score' => $game->home_score,
            'away_score' => $game->away_score,
            'events' => $game->events->map(fn ($event) => [
                'id' => $event->id,
                'type' => $event->type->value,
                'label' => $event->type->label(),
                'minute' => $event->minute,
                'player' => $event->player?->name,
                'team_id' => $event->player?->team_id,
                'assist' => $event->assist?->name,
                'secondary' => $event->replacement?->name,
            ])->values()->all(),
        ];
    }
}
