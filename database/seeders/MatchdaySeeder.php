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
            'name' => 'Марта Коваленко',
            'email' => 'admin@matchday.test',
        ]);

        $referees = collect([
            ['Андрій Лисенко', 'referee@matchday.test'],
            ['Ірина Савчук', 'referee2@matchday.test'],
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
                'name' => 'Капітан '.$club['name'],
                'email' => $index === 0 ? 'captain@matchday.test' : 'captain'.($index + 1).'@matchday.test',
            ]);

            return $team;
        });

        $tournament = Tournament::query()->create([
            'name' => 'Matchday Ліга',
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
                        'venue' => $game->homeTeam->city.' Арена',
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

        $first = ['Андрій', 'Олег', 'Тарас', 'Максим', 'Денис', 'Іван', 'Богдан', 'Роман', 'Юрій', 'Назар', 'Владислав', 'Артем', 'Кирило', 'Остап', 'Марко', 'Сергій', 'Павло', 'Ілля', 'Данило', 'Єгор', 'Микола', 'Василь', 'Григорій', 'Леонід', 'Степан', 'Федір', 'Ярослав', 'Захар', 'Матвій', 'Олександр'];
        $last = ['Коваленко', 'Шевченко', 'Бондар', 'Мельник', 'Ткаченко', 'Кравченко', 'Олійник', 'Шевчук', 'Бондаренко', 'Ткачук', 'Мороз', 'Лисенко', 'Савченко', 'Петренко', 'Іваненко', 'Бондарчук', 'Романюк', 'Гнатюк', 'Сидоренко', 'Павленко', 'Козак', 'Левченко', 'Марчук', 'Данилюк', 'Поліщук', 'Семенюк', 'Яременко', 'Білоус', 'Дорошенко', 'Кушнір'];

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
            ['name' => 'Буревій', 'slug' => 'bureviy', 'city' => 'Київ', 'primary' => '#d6ff3f', 'secondary' => '#102018'],
            ['name' => 'Хвиля', 'slug' => 'khvylia', 'city' => 'Одеса', 'primary' => '#3ec6ff', 'secondary' => '#071820'],
            ['name' => 'Скеля', 'slug' => 'skelia', 'city' => 'Львів', 'primary' => '#ffb020', 'secondary' => '#1a1206'],
            ['name' => 'Фортеця', 'slug' => 'fortetsia', 'city' => 'Харків', 'primary' => '#ff5a36', 'secondary' => '#1a0c08'],
            ['name' => 'Прибій', 'slug' => 'prybiy', 'city' => 'Миколаїв', 'primary' => '#7aa2ff', 'secondary' => '#0c1224'],
            ['name' => 'Степ', 'slug' => 'step', 'city' => 'Запоріжжя', 'primary' => '#f2e27a', 'secondary' => '#1a180c'],
            ['name' => 'Політ', 'slug' => 'polit', 'city' => 'Дніпро', 'primary' => '#c084fc', 'secondary' => '#140c1c'],
            ['name' => 'Замок', 'slug' => 'zamok', 'city' => 'Луцьк', 'primary' => '#9ae6b4', 'secondary' => '#0c1812'],
            ['name' => 'Маяк', 'slug' => 'maiak', 'city' => 'Черкаси', 'primary' => '#fb7185', 'secondary' => '#1c0c12'],
            ['name' => 'Вир', 'slug' => 'vyr', 'city' => 'Полтава', 'primary' => '#67e8f9', 'secondary' => '#071418'],
        ];
    }
}
