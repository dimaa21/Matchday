<?php

namespace Database\Seeders;

use App\Enums\MatchEventType;
use App\Enums\MatchStatus;
use App\Enums\PlayerPosition;
use App\Enums\TournamentFormat;
use App\Enums\TournamentStatus;
use App\Models\Game;
use App\Models\Player;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use App\Services\FixtureScheduler;
use App\Services\LiveMatchService;
use App\Services\SuspensionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class MatchdaySeeder extends Seeder
{
    private int $minute = 1;

    public function run(): void
    {
        mt_srand(20261111);

        User::factory()->admin()->create([
            'name' => 'Marta Cole',
            'email' => 'admin@matchday.test',
        ]);

        $referees = collect([
            ['Andrew Lane', 'referee@matchday.test'],
            ['Irene Shaw', 'referee2@matchday.test'],
        ])->map(fn (array $row) => User::factory()->referee()->create([
            'name' => $row[0],
            'email' => $row[1],
        ]));

        $teams = collect($this->clubs())->values()->map(function (array $club, int $index) {
            $team = Team::query()->create([
                'name' => $club['name'],
                'slug' => $club['slug'],
                'city' => $club['city'],
                'primary_color' => $club['primary'],
                'secondary_color' => $club['secondary'],
            ]);

            $this->squad($team, $index);

            User::factory()->captain($team)->create([
                'name' => 'Captain '.$club['name'],
                'email' => $index === 0 ? 'captain@matchday.test' : 'captain'.($index + 1).'@matchday.test',
            ]);

            return $team;
        });

        $tournament = Tournament::query()->create([
            'name' => 'Matchday League',
            'slug' => 'matchday-liga',
            'season' => '2026/27',
            'format' => TournamentFormat::League,
            'status' => TournamentStatus::Active,
            'points_for_win' => 3,
            'points_for_draw' => 1,
            'legs' => 2,
        ]);

        $tournament->teams()->attach($teams->pluck('id'));

        app(FixtureScheduler::class)->generate($tournament, Carbon::parse('2026-09-05 16:00:00'));

        $anchor = now()->startOfDay();
        $livePinned = false;

        $tournament->games()
            ->with(['homeTeam.players', 'awayTeam.players'])
            ->where('is_bye', false)
            ->orderBy('round')
            ->orderBy('id')
            ->get()
            ->groupBy('round')
            ->each(function (Collection $matches, $round) use ($anchor, $referees, &$livePinned) {
                $round = (int) $round;
                foreach ($matches->values() as $index => $game) {
                    $kickoff = $anchor->copy()->addWeeks($round - 10)->setTime($index === 0 ? 16 : 18, 0);
                    $game->update([
                        'kickoff_at' => $round === 10 && $index === 0 ? now()->subMinutes(18) : $kickoff,
                        'referee_id' => $referees[$index % $referees->count()]->id,
                        'venue' => $game->homeTeam->city.' Arena',
                    ]);

                    if ($round < 10) {
                        $this->play($game, $round === 8 && $index === 0);
                    } elseif ($round === 10 && ! $livePinned && $index === 0) {
                        $this->kickOff($game);
                        $livePinned = true;
                    } else {
                        $this->writeLineup($game, $game->homeTeam);
                        $this->writeLineup($game, $game->awayTeam);
                    }
                }
            });
    }

    private function play(Game $game, bool $scriptRed): void
    {
        $this->minute = random_int(1, 6);
        $this->writeLineup($game, $game->homeTeam);
        $this->writeLineup($game, $game->awayTeam);
        $this->score($game, $game->homeTeam, $this->pick([0, 0, 1, 1, 1, 2, 2, 2, 3, 4]));
        $this->score($game, $game->awayTeam, $this->pick([0, 0, 0, 1, 1, 1, 2, 2, 3]));
        $this->cards($game);

        if ($scriptRed) {
            $star = $game->homeTeam->players->firstWhere('squad_number', 9);

            if ($star && ! app(SuspensionService::class)->isSuspended($star, $game)) {
                $game->events()->create([
                    'player_id' => $star->id,
                    'type' => MatchEventType::Red,
                    'minute' => 81,
                ]);
            }
        }

        app(LiveMatchService::class)->syncScore($game);
        $game->update(['status' => MatchStatus::Finished]);
    }

    private function kickOff(Game $game): void
    {
        $this->minute = 12;
        $this->writeLineup($game, $game->homeTeam);
        $this->writeLineup($game, $game->awayTeam);
        $this->score($game, $game->homeTeam, 1);
        app(LiveMatchService::class)->syncScore($game);
        $game->update(['status' => MatchStatus::Live]);
    }

    private function score(Game $game, Team $team, int $goals): void
    {
        for ($goal = 0; $goal < $goals; $goal++) {
            $scorer = $this->scorer($game, $team);

            if ($scorer === null) {
                return;
            }

            $assist = random_int(1, 100) <= 55 ? $this->assistant($game, $team, $scorer) : null;

            $game->events()->create([
                'player_id' => $scorer->id,
                'type' => random_int(1, 100) <= 14 ? MatchEventType::Penalty : MatchEventType::Goal,
                'minute' => $this->nextMinute(),
                'assist_player_id' => $assist?->id,
            ]);
        }
    }

    private function cards(Game $game): void
    {
        $yellows = $this->pick([0, 0, 1, 1, 1, 2]);

        for ($card = 0; $card < $yellows; $card++) {
            $player = $this->anyone($game);

            if ($player === null) {
                return;
            }

            $game->events()->create([
                'player_id' => $player->id,
                'type' => MatchEventType::Yellow,
                'minute' => $this->nextMinute(),
            ]);
        }

        if (random_int(1, 100) <= 8) {
            $player = $this->anyone($game);

            if ($player !== null) {
                $game->events()->create([
                    'player_id' => $player->id,
                    'type' => MatchEventType::Red,
                    'minute' => $this->nextMinute(),
                ]);
            }
        }
    }

    private function scorer(Game $game, Team $team): ?Player
    {
        $bag = [];

        foreach ($this->available($game, $team) as $player) {
            $weight = match ($player->position) {
                PlayerPosition::Forward => 6,
                PlayerPosition::Midfielder => 3,
                PlayerPosition::Defender => 1,
                PlayerPosition::Goalkeeper => 0,
            };

            for ($i = 0; $i < $weight; $i++) {
                $bag[] = $player;
            }
        }

        return $bag === [] ? $this->available($game, $team)->first() : $bag[array_rand($bag)];
    }

    private function assistant(Game $game, Team $team, Player $scorer): ?Player
    {
        $options = $this->available($game, $team)->where('id', '!=', $scorer->id)->values();

        return $options->isEmpty() ? null : $options->random();
    }

    private function anyone(Game $game): ?Player
    {
        $players = $this->available($game, $game->homeTeam)
            ->concat($this->available($game, $game->awayTeam));

        return $players->isEmpty() ? null : $players->random();
    }

    /**
     * @return Collection<int, Player>
     */
    private function available(Game $game, Team $team): Collection
    {
        $suspensions = app(SuspensionService::class);

        return $team->players->reject(
            fn (Player $player) => $suspensions->isSuspended($player, $game),
        )->values();
    }

    private function writeLineup(Game $game, Team $team): void
    {
        $available = $this->available($game, $team)->sortBy('squad_number')->values();

        foreach ($available->take(15) as $index => $player) {
            $game->squads()->create([
                'team_id' => $team->id,
                'player_id' => $player->id,
                'is_starter' => $index < 11,
            ]);
        }
    }

    private function squad(Team $team, int $teamIndex): void
    {
        $positions = [
            1 => PlayerPosition::Goalkeeper,
            12 => PlayerPosition::Goalkeeper,
            2 => PlayerPosition::Defender,
            3 => PlayerPosition::Defender,
            4 => PlayerPosition::Defender,
            5 => PlayerPosition::Defender,
            6 => PlayerPosition::Defender,
            7 => PlayerPosition::Midfielder,
            8 => PlayerPosition::Midfielder,
            10 => PlayerPosition::Midfielder,
            14 => PlayerPosition::Midfielder,
            15 => PlayerPosition::Midfielder,
            9 => PlayerPosition::Forward,
            11 => PlayerPosition::Forward,
            13 => PlayerPosition::Forward,
        ];

        $first = ['Andrew', 'Owen', 'Thomas', 'Max', 'Dennis', 'John', 'Ben', 'Roman', 'George', 'Nathan', 'Victor', 'Arthur', 'Chris', 'Oscar', 'Mark', 'Simon', 'Paul', 'Leo', 'Daniel', 'Edward', 'Nicholas', 'William', 'Gregory', 'Leon', 'Stephen', 'Fred', 'Jacob', 'Zach', 'Matthew', 'Alexander'];
        $last = ['Cole', 'Shaw', 'Bond', 'Miller', 'Taylor', 'Clark', 'Owen', 'Brooks', 'Baker', 'Morris', 'Frost', 'Lane', 'Sawyer', 'Peters', 'Harris', 'Grant', 'Stone', 'Palmer', 'Cox', 'Lewis', 'March', 'Daniels', 'Bell', 'Cooper', 'Walsh', 'Reed', 'Hayes', 'Bennett', 'Porter', 'Knight'];

        foreach ($positions as $number => $position) {
            $cursor = ($teamIndex * 15 + $number) % 30;

            Player::query()->create([
                'team_id' => $team->id,
                'name' => $first[$cursor].' '.$last[($cursor + $teamIndex) % 30],
                'squad_number' => $number,
                'position' => $position,
            ]);
        }
    }

    private function nextMinute(): int
    {
        $this->minute = min(90, $this->minute + random_int(1, 7));

        return $this->minute;
    }

    private function pick(array $values): mixed
    {
        return $values[array_rand($values)];
    }

    /**
     * @return list<array{name: string, slug: string, city: string, primary: string, secondary: string}>
     */
    private function clubs(): array
    {
        return [
            ['name' => 'Gale', 'slug' => 'bureviy', 'city' => 'Kyiv', 'primary' => '#1d4ed8', 'secondary' => '#ffffff'],
            ['name' => 'Wave', 'slug' => 'khvylia', 'city' => 'Odesa', 'primary' => '#0e7490', 'secondary' => '#ffffff'],
            ['name' => 'Rock', 'slug' => 'skelia', 'city' => 'Lviv', 'primary' => '#b45309', 'secondary' => '#ffffff'],
            ['name' => 'Fortress', 'slug' => 'fortetsia', 'city' => 'Kharkiv', 'primary' => '#b91c1c', 'secondary' => '#ffffff'],
            ['name' => 'Surf', 'slug' => 'prybiy', 'city' => 'Mykolaiv', 'primary' => '#0369a1', 'secondary' => '#ffffff'],
            ['name' => 'Steppe', 'slug' => 'step', 'city' => 'Zaporizhzhia', 'primary' => '#a16207', 'secondary' => '#111111'],
            ['name' => 'Flight', 'slug' => 'polit', 'city' => 'Dnipro', 'primary' => '#6d28d9', 'secondary' => '#ffffff'],
            ['name' => 'Castle', 'slug' => 'zamok', 'city' => 'Lutsk', 'primary' => '#166534', 'secondary' => '#ffffff'],
            ['name' => 'Beacon', 'slug' => 'maiak', 'city' => 'Cherkasy', 'primary' => '#be123c', 'secondary' => '#ffffff'],
            ['name' => 'Whirl', 'slug' => 'vyr', 'city' => 'Poltava', 'primary' => '#0f766e', 'secondary' => '#ffffff'],
        ];
    }
}
