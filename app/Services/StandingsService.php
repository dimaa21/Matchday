<?php

namespace App\Services;

use App\Enums\MatchStatus;
use App\Models\Game;
use App\Models\Tournament;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class StandingsService
{
    /**
     * @return list<array{group: ?string, rows: list<array<string, mixed>>}>
     */
    public function tables(Tournament $tournament): array
    {
        if ($tournament->format->value === 'groups_playoff') {
            $groups = $tournament->teams()
                ->get()
                ->pluck('pivot.group_name')
                ->filter()
                ->unique()
                ->sort()
                ->values();

            return $groups->map(fn (string $group) => [
                'group' => $group,
                'rows' => $this->calculate($tournament, $group),
            ])->all();
        }

        return [[
            'group' => null,
            'rows' => $this->calculate($tournament),
        ]];
    }

    /**
     * @return list<array{group: ?string, rows: list<array<string, mixed>>}>
     */
    public function cached(Tournament $tournament): array
    {
        return Cache::remember(
            $this->cacheKey($tournament),
            now()->addDay(),
            fn () => $this->tables($tournament),
        );
    }

    public function invalidate(Tournament $tournament): void
    {
        Cache::forget($this->cacheKey($tournament));
    }

    public function cacheKey(Tournament $tournament): string
    {
        return "tournaments.{$tournament->id}.standings";
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function calculate(Tournament $tournament, ?string $group = null): array
    {
        $teams = $tournament->teams();

        if ($group !== null) {
            $teams->wherePivot('group_name', '=', $group);
        }

        $teams = $teams->orderBy('teams.name')->get();

        $matches = $tournament->games()
            ->where('status', MatchStatus::Finished)
            ->where('is_bye', false)
            ->whereNotNull('home_team_id')
            ->whereNotNull('away_team_id')
            ->when($group !== null, fn ($query) => $query->where('matches.group_name', $group))
            ->orderBy('round')
            ->orderBy('kickoff_at')
            ->orderBy('id')
            ->get();

        $rows = [];

        foreach ($teams as $team) {
            $rows[$team->id] = [
                'team_id' => $team->id,
                'team_name' => $team->name,
                'team_slug' => $team->slug,
                'team_city' => $team->city,
                'primary_color' => $team->primary_color,
                'secondary_color' => $team->secondary_color,
                'played' => 0,
                'won' => 0,
                'drawn' => 0,
                'lost' => 0,
                'goals_for' => 0,
                'goals_against' => 0,
                'goal_difference' => 0,
                'points' => 0,
                'form' => [],
            ];
        }

        foreach ($matches as $match) {
            $this->applyMatch($rows, $match, $tournament->points_for_win, $tournament->points_for_draw);
        }

        foreach ($rows as &$row) {
            $row['form'] = array_slice($row['form'], -5);
        }
        unset($row);

        $ordered = $this->order(array_values($rows), $matches, $tournament->points_for_win, $tournament->points_for_draw);

        foreach ($ordered as $index => &$row) {
            $row['position'] = $index + 1;
        }
        unset($row);

        return $ordered;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function applyMatch(array &$rows, Game $match, int $winPoints, int $drawPoints): void
    {
        if (! isset($rows[$match->home_team_id], $rows[$match->away_team_id])) {
            return;
        }

        $homeResult = $this->result($match->home_score, $match->away_score);
        $awayResult = $this->result($match->away_score, $match->home_score);

        $this->applySide($rows[$match->home_team_id], $match->home_score, $match->away_score, $homeResult, $winPoints, $drawPoints);
        $this->applySide($rows[$match->away_team_id], $match->away_score, $match->home_score, $awayResult, $winPoints, $drawPoints);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function applySide(array &$row, int $goalsFor, int $goalsAgainst, string $result, int $winPoints, int $drawPoints): void
    {
        $row['played']++;
        $row['goals_for'] += $goalsFor;
        $row['goals_against'] += $goalsAgainst;
        $row['goal_difference'] = $row['goals_for'] - $row['goals_against'];
        $row['form'][] = $result;

        if ($result === 'W') {
            $row['won']++;
            $row['points'] += $winPoints;
        } elseif ($result === 'D') {
            $row['drawn']++;
            $row['points'] += $drawPoints;
        } else {
            $row['lost']++;
        }
    }

    private function result(int $goalsFor, int $goalsAgainst): string
    {
        if ($goalsFor > $goalsAgainst) {
            return 'W';
        }

        if ($goalsFor === $goalsAgainst) {
            return 'D';
        }

        return 'L';
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  Collection<int, Game>  $matches
     * @return list<array<string, mixed>>
     */
    private function order(array $rows, Collection $matches, int $winPoints, int $drawPoints): array
    {
        $buckets = [];

        foreach ($rows as $row) {
            $buckets[$row['points']][] = $row;
        }

        krsort($buckets, SORT_NUMERIC);

        $ordered = [];

        foreach ($buckets as $bucket) {
            array_push($ordered, ...$this->breakPointsTie($bucket, $matches, $winPoints, $drawPoints));
        }

        return $ordered;
    }

    /**
     * @param  list<array<string, mixed>>  $bucket
     * @param  Collection<int, Game>  $matches
     * @return list<array<string, mixed>>
     */
    private function breakPointsTie(array $bucket, Collection $matches, int $winPoints, int $drawPoints): array
    {
        if (count($bucket) <= 1) {
            return $bucket;
        }

        $groups = $this->splitByHeadToHead($bucket, $matches, $winPoints, $drawPoints);

        if ($groups === null) {
            return $this->sortByOverall($bucket);
        }

        $ordered = [];

        foreach ($groups as $group) {
            array_push($ordered, ...$this->breakPointsTie($group, $matches, $winPoints, $drawPoints));
        }

        return $ordered;
    }

    /**
     * @param  list<array<string, mixed>>  $bucket
     * @param  Collection<int, Game>  $matches
     * @return list<list<array<string, mixed>>>|null
     */
    private function splitByHeadToHead(array $bucket, Collection $matches, int $winPoints, int $drawPoints): ?array
    {
        $ids = array_column($bucket, 'team_id');
        $mini = [];

        foreach ($ids as $id) {
            $mini[$id] = ['points' => 0, 'goal_difference' => 0, 'goals_for' => 0];
        }

        foreach ($matches as $match) {
            if (! in_array($match->home_team_id, $ids, true) || ! in_array($match->away_team_id, $ids, true)) {
                continue;
            }

            $this->applyMini($mini[$match->home_team_id], $match->home_score, $match->away_score, $winPoints, $drawPoints);
            $this->applyMini($mini[$match->away_team_id], $match->away_score, $match->home_score, $winPoints, $drawPoints);
        }

        $grouped = [];

        foreach ($bucket as $row) {
            $record = $mini[$row['team_id']];
            $key = $record['points'].':'.$record['goal_difference'].':'.$record['goals_for'];
            $grouped[$key][] = $row;
        }

        if (count($grouped) <= 1) {
            return null;
        }

        uksort($grouped, function (string $left, string $right): int {
            [$leftPoints, $leftDifference, $leftGoals] = array_map('intval', explode(':', $left));
            [$rightPoints, $rightDifference, $rightGoals] = array_map('intval', explode(':', $right));

            return $rightPoints <=> $leftPoints
                ?: $rightDifference <=> $leftDifference
                ?: $rightGoals <=> $leftGoals;
        });

        return array_values($grouped);
    }

    /**
     * @param  array{points: int, goal_difference: int, goals_for: int}  $row
     */
    private function applyMini(array &$row, int $goalsFor, int $goalsAgainst, int $winPoints, int $drawPoints): void
    {
        $row['goals_for'] += $goalsFor;
        $row['goal_difference'] += $goalsFor - $goalsAgainst;

        if ($goalsFor > $goalsAgainst) {
            $row['points'] += $winPoints;
        } elseif ($goalsFor === $goalsAgainst) {
            $row['points'] += $drawPoints;
        }
    }

    /**
     * @param  list<array<string, mixed>>  $bucket
     * @return list<array<string, mixed>>
     */
    private function sortByOverall(array $bucket): array
    {
        usort($bucket, function (array $left, array $right): int {
            return $right['goal_difference'] <=> $left['goal_difference']
                ?: $right['goals_for'] <=> $left['goals_for']
                ?: strnatcasecmp($left['team_name'], $right['team_name']);
        });

        return $bucket;
    }
}
