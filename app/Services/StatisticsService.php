<?php

namespace App\Services;

use App\Enums\MatchEventType;
use App\Enums\MatchStatus;
use App\Models\Game;
use App\Models\MatchEvent;
use App\Models\Player;
use App\Models\Tournament;
use Illuminate\Support\Facades\DB;

class StatisticsService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function topScorers(Tournament $tournament, ?int $limit = 10): array
    {
        $query = MatchEvent::query()
            ->select([
                'player_id',
                DB::raw('COUNT(*) as goals'),
                DB::raw("SUM(CASE WHEN type = 'penalty' THEN 1 ELSE 0 END) as penalties"),
            ])
            ->whereIn('type', [MatchEventType::Goal->value, MatchEventType::Penalty->value])
            ->whereHas('game', fn ($builder) => $builder
                ->where('tournament_id', $tournament->id)
                ->where('status', MatchStatus::Finished))
            ->groupBy('player_id')
            ->orderByDesc('goals')
            ->orderBy('player_id');

        if ($limit !== null) {
            $query->limit($limit);
        }

        $rows = $query->get();
        $players = Player::query()->with('team')->whereIn('id', $rows->pluck('player_id'))->get()->keyBy('id');
        $assists = $this->assistMap($tournament);

        return $rows->map(function ($row) use ($players, $assists) {
            $player = $players->get($row->player_id);

            return [
                'player_id' => $row->player_id,
                'player_name' => $player?->name,
                'squad_number' => $player?->squad_number,
                'position' => $player?->position?->value,
                'team_id' => $player?->team_id,
                'team_name' => $player?->team?->name,
                'team_slug' => $player?->team?->slug,
                'primary_color' => $player?->team?->primary_color,
                'goals' => (int) $row->goals,
                'penalties' => (int) $row->penalties,
                'assists' => $assists[$row->player_id] ?? 0,
            ];
        })->all();
    }

    /**
     * @return array<string, int>
     */
    public function forPlayer(Player $player): array
    {
        $events = MatchEvent::query()->where('player_id', $player->id)->get();

        return [
            'goals' => $events->whereIn('type', [MatchEventType::Goal, MatchEventType::Penalty])->count(),
            'penalties' => $events->where('type', MatchEventType::Penalty)->count(),
            'own_goals' => $events->where('type', MatchEventType::OwnGoal)->count(),
            'assists' => MatchEvent::query()->where('assist_player_id', $player->id)->count(),
            'yellows' => $events->where('type', MatchEventType::Yellow)->count(),
            'reds' => $events->where('type', MatchEventType::Red)->count(),
            'matches' => $player->squads()->distinct('match_id')->count('match_id'),
        ];
    }

    /**
     * @return array<string, int>
     */
    public function forTeam(int $teamId, ?int $tournamentId = null): array
    {
        $games = Game::query()
            ->where('is_bye', false)
            ->where('status', MatchStatus::Finished)
            ->when($tournamentId, fn ($query) => $query->where('tournament_id', $tournamentId))
            ->where(function ($query) use ($teamId) {
                $query->where('home_team_id', $teamId)->orWhere('away_team_id', $teamId);
            })
            ->get();

        $stats = ['played' => 0, 'won' => 0, 'drawn' => 0, 'lost' => 0, 'goals_for' => 0, 'goals_against' => 0];

        foreach ($games as $game) {
            $for = $game->home_team_id === $teamId ? $game->home_score : $game->away_score;
            $against = $game->home_team_id === $teamId ? $game->away_score : $game->home_score;
            $stats['played']++;
            $stats['goals_for'] += $for;
            $stats['goals_against'] += $against;
            $stats[$for > $against ? 'won' : ($for === $against ? 'drawn' : 'lost')]++;
        }

        return $stats;
    }

    /**
     * @return array<int, int>
     */
    private function assistMap(Tournament $tournament): array
    {
        return MatchEvent::query()
            ->select('assist_player_id', DB::raw('COUNT(*) as assists'))
            ->whereNotNull('assist_player_id')
            ->whereIn('type', [MatchEventType::Goal->value, MatchEventType::Penalty->value])
            ->whereHas('game', fn ($builder) => $builder
                ->where('tournament_id', $tournament->id)
                ->where('status', MatchStatus::Finished))
            ->groupBy('assist_player_id')
            ->pluck('assists', 'assist_player_id')
            ->map(fn ($count) => (int) $count)
            ->all();
    }
}
