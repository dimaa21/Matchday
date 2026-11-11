<?php

namespace App\Services;

use App\Enums\MatchEventType;
use App\Enums\MatchStatus;
use App\Models\Game;
use App\Models\Player;

class SuspensionService
{
    public function isSuspended(Player $player, Game $game): bool
    {
        return $this->banFor($player, $game) !== null;
    }

    /**
     * Reason the player must sit out this match: a red card, or every third yellow.
     * Bans earned in the same match are served on consecutive fixtures, red first.
     */
    public function banFor(Player $player, Game $game): ?string
    {
        if ($player->team_id === null) {
            return null;
        }

        $fixtures = Game::query()
            ->where('tournament_id', $game->tournament_id)
            ->where('is_bye', false)
            ->where(function ($query) use ($player) {
                $query->where('home_team_id', $player->team_id)
                    ->orWhere('away_team_id', $player->team_id);
            })
            ->orderBy('round')
            ->orderBy('kickoff_at')
            ->orderBy('id')
            ->with(['events' => fn ($query) => $query->where('player_id', $player->id)])
            ->get();

        $yellows = 0;
        $bans = [];

        foreach ($fixtures as $fixture) {
            if ($bans !== []) {
                $reason = array_shift($bans);

                if ($fixture->id === $game->id) {
                    return $reason;
                }

                continue;
            }

            if ($fixture->id === $game->id) {
                return null;
            }

            if ($fixture->status !== MatchStatus::Finished) {
                continue;
            }

            $reds = $fixture->events->where('type', MatchEventType::Red)->count();
            $yellowCount = $fixture->events->where('type', MatchEventType::Yellow)->count();

            if ($reds > 0) {
                $bans[] = 'red';
            }

            $yellows += $yellowCount;

            while ($yellows >= 3) {
                $yellows -= 3;
                $bans[] = 'accumulated_yellows';
            }
        }

        return null;
    }

    /**
     * @return list<int>
     */
    public function bannedPlayerIds(Game $game): array
    {
        $teamIds = array_filter([$game->home_team_id, $game->away_team_id]);

        if ($teamIds === []) {
            return [];
        }

        return Player::query()
            ->whereIn('team_id', $teamIds)
            ->get()
            ->filter(fn (Player $player) => $this->isSuspended($player, $game))
            ->pluck('id')
            ->all();
    }
}
