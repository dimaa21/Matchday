<?php

namespace App\Services;

use App\Enums\MatchStatus;
use App\Enums\TournamentFormat;
use App\Listeners\AdvanceKnockoutWinner;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class FixtureScheduler
{
    public function __construct(
        private readonly FixtureGenerator $generator,
        private readonly AdvanceKnockoutWinner $advancer,
        private readonly StandingsService $standings,
    ) {}

    public function generate(Tournament $tournament, ?Carbon $startsAt = null): int
    {
        $teams = $tournament->teams()->orderBy('name')->get();

        if ($teams->count() < 2) {
            throw ValidationException::withMessages([
                'teams' => 'A calendar needs at least two teams.',
            ]);
        }

        $this->guardAgainstPlayedMatches($tournament);
        $tournament->games()->delete();

        $kickoff = ($startsAt ?? now()->next('Saturday'))->copy()->setTime(16, 0);

        $count = match ($tournament->format) {
            TournamentFormat::Cup => $this->createKnockout($tournament, $teams->pluck('id')->all(), $kickoff),
            TournamentFormat::GroupsPlayoff => $this->createGroups($tournament, $teams, $kickoff),
            default => $this->createLeague($tournament, $teams, $kickoff),
        };

        $this->standings->invalidate($tournament);

        return $count;
    }

    public function generatePlayoff(Tournament $tournament): int
    {
        if ($tournament->format !== TournamentFormat::GroupsPlayoff) {
            throw ValidationException::withMessages([
                'format' => 'Playoffs are only available for the groups + playoff format.',
            ]);
        }

        $unfinished = $tournament->games()
            ->whereNotNull('group_name')
            ->where('is_bye', false)
            ->where('status', '!=', MatchStatus::Finished)
            ->exists();

        if ($unfinished) {
            throw ValidationException::withMessages([
                'fixtures' => 'Finish every group-stage match first.',
            ]);
        }

        if ($tournament->games()->whereNull('group_name')->exists()) {
            throw ValidationException::withMessages([
                'fixtures' => 'The playoff bracket already exists.',
            ]);
        }

        $seeds = $this->playoffSeeds($tournament);

        if (count($seeds) < 2) {
            throw ValidationException::withMessages([
                'fixtures' => 'Not enough teams for a playoff.',
            ]);
        }

        $startRound = (int) $tournament->games()->max('round') + 1;
        $kickoff = now()->next('Saturday')->setTime(18, 0);

        return $this->createKnockout($tournament, $seeds, $kickoff, $startRound);
    }

    /**
     * @param  Collection<int, Team>  $teams
     */
    private function createLeague(Tournament $tournament, Collection $teams, Carbon $kickoff): int
    {
        $rounds = $this->generator->roundRobin(
            $teams->pluck('id')->all(),
            $tournament->legs >= 2,
        );

        return $this->persistRounds($tournament, $rounds, $kickoff, $teams);
    }

    /**
     * @param  Collection<int, Team>  $teams
     */
    private function createGroups(Tournament $tournament, Collection $teams, Carbon $kickoff): int
    {
        $grouped = $teams->groupBy(fn (Team $team) => $team->pivot->group_name ?: 'A');
        $created = 0;

        foreach ($grouped as $group => $groupTeams) {
            $rounds = $this->generator->roundRobin($groupTeams->pluck('id')->all(), false);

            foreach ($rounds as &$pairings) {
                foreach ($pairings as &$pairing) {
                    $pairing['group_name'] = (string) $group;
                }
            }
            unset($pairings, $pairing);

            $created += $this->persistRounds($tournament, $rounds, $kickoff, $groupTeams);
        }

        return $created;
    }

    /**
     * @param  list<int>  $teamIds
     */
    private function createKnockout(Tournament $tournament, array $teamIds, Carbon $kickoff, int $roundOffset = 0): int
    {
        $blueprint = $this->generator->knockout($teamIds);
        $ids = [];
        $teams = Team::query()->whereIn('id', $teamIds)->get()->keyBy('id');

        foreach ($blueprint as $item) {
            $round = $item['round'] + $roundOffset;
            $home = $item['home_team_id'] ? $teams->get($item['home_team_id']) : null;
            $game = $tournament->games()->create([
                'round' => $round,
                'bracket_slot' => $item['bracket_slot'],
                'home_team_id' => $item['home_team_id'],
                'away_team_id' => $item['away_team_id'],
                'is_bye' => $item['is_bye'],
                'status' => $item['is_bye'] ? MatchStatus::Finished : MatchStatus::Scheduled,
                'kickoff_at' => $item['is_bye'] ? null : $kickoff->copy()->addWeeks($item['round'] - 1),
                'venue' => $home?->city ? $home->city.' Arena' : null,
            ]);

            $ids[$item['round'].':'.$item['bracket_slot']] = $game;
        }

        foreach ($blueprint as $item) {
            if ($item['next_round'] === null) {
                continue;
            }

            $game = $ids[$item['round'].':'.$item['bracket_slot']];
            $next = $ids[$item['next_round'].':'.$item['next_slot']];
            $game->update([
                'next_match_id' => $next->id,
                'next_slot' => $item['next_side'],
            ]);
        }

        foreach ($blueprint as $item) {
            if (! $item['is_bye']) {
                continue;
            }

            $game = $ids[$item['round'].':'.$item['bracket_slot']]->fresh();
            $this->advancer->advance($game);
        }

        return count($ids);
    }

    /**
     * @param  list<list<array<string, mixed>>>  $rounds
     * @param  Collection<int, Team>  $teams
     */
    private function persistRounds(Tournament $tournament, array $rounds, Carbon $kickoff, Collection $teams): int
    {
        $byId = $teams->keyBy('id');
        $created = 0;

        foreach ($rounds as $pairings) {
            foreach ($pairings as $pairing) {
                $home = $pairing['home_team_id'] ? $byId->get($pairing['home_team_id']) : null;

                $tournament->games()->create([
                    'round' => $pairing['round'],
                    'group_name' => $pairing['group_name'],
                    'home_team_id' => $pairing['home_team_id'],
                    'away_team_id' => $pairing['away_team_id'],
                    'is_bye' => $pairing['is_bye'],
                    'status' => $pairing['is_bye'] ? MatchStatus::Finished : MatchStatus::Scheduled,
                    'kickoff_at' => $pairing['is_bye'] ? null : $kickoff->copy()->addWeeks($pairing['round'] - 1),
                    'venue' => $home?->city ? $home->city.' Arena' : null,
                ]);
                $created++;
            }
        }

        return $created;
    }

    /**
     * @return list<int>
     */
    private function playoffSeeds(Tournament $tournament): array
    {
        $groups = $tournament->teams()->get()
            ->pluck('pivot.group_name')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $qualifiers = [];

        foreach ($groups as $group) {
            $table = $this->standings->calculate($tournament, $group);
            $take = $groups->count() === 2 ? 2 : 1;
            $qualifiers[$group] = array_slice(array_column($table, 'team_id'), 0, $take);
        }

        if ($groups->count() === 2) {
            $first = $groups[0];
            $second = $groups[1];

            return array_values(array_filter([
                $qualifiers[$first][0] ?? null,
                $qualifiers[$second][1] ?? null,
                $qualifiers[$second][0] ?? null,
                $qualifiers[$first][1] ?? null,
            ]));
        }

        return array_values(array_filter(array_map(
            fn (string $group) => $qualifiers[$group][0] ?? null,
            $groups->all(),
        )));
    }

    private function guardAgainstPlayedMatches(Tournament $tournament): void
    {
        $played = $tournament->games()
            ->where('is_bye', false)
            ->where('status', '!=', MatchStatus::Scheduled)
            ->exists();

        if ($played) {
            throw ValidationException::withMessages([
                'fixtures' => 'The calendar cannot be regenerated because some matches have already been played.',
            ]);
        }
    }
}
