<?php

namespace App\Listeners;

use App\Enums\MatchStatus;
use App\Events\MatchFinished;
use App\Models\Game;

class AdvanceKnockoutWinner
{
    public function handle(MatchFinished $event): void
    {
        $this->advance($event->game);
    }

    public function advance(Game $game, ?int $winnerId = null): void
    {
        $winnerId ??= $this->winnerId($game);

        if ($winnerId === null || $game->next_match_id === null) {
            return;
        }

        $column = $game->next_slot === 'away' ? 'away_team_id' : 'home_team_id';

        Game::query()->whereKey($game->next_match_id)->update([
            $column => $winnerId,
        ]);
    }

    public function winnerId(Game $game): ?int
    {
        if ($game->is_bye) {
            return $game->home_team_id ?? $game->away_team_id;
        }

        if ($game->status !== MatchStatus::Finished || $game->home_score === $game->away_score) {
            return null;
        }

        return $game->home_score > $game->away_score
            ? $game->home_team_id
            : $game->away_team_id;
    }
}
