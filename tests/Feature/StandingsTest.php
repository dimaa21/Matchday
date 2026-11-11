<?php

use App\Enums\MatchEventType;
use App\Enums\PlayerPosition;
use App\Events\MatchFinished;
use App\Listeners\RefreshStandings;
use App\Models\Game;
use App\Models\Player;
use App\Models\Team;
use App\Models\Tournament;
use App\Services\StandingsService;
use App\Services\StatisticsService;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;

function attachLeague(int $count = 4): array
{
    $tournament = Tournament::factory()->create();
    $teams = Team::factory()->count($count)->create();
    $tournament->teams()->attach($teams->pluck('id'));

    return [$tournament, $teams];
}

function play(Tournament $tournament, Team $home, Team $away, int $homeScore, int $awayScore, int $round = 1): Game
{
    return Game::factory()->finished($homeScore, $awayScore)->create([
        'tournament_id' => $tournament->id,
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
        'round' => $round,
        'kickoff_at' => now()->addDays($round),
    ]);
}

function names(array $rows): array
{
    return array_column($rows, 'team_name');
}

it('awards win and draw points and keeps the last five results', function () {
    [$tournament, $teams] = attachLeague(3);
    $tournament->update(['points_for_win' => 2, 'points_for_draw' => 1]);
    [$alpha, $bravo, $charlie] = $teams->all();

    play($tournament, $alpha, $bravo, 2, 0, 1);
    play($tournament, $alpha, $charlie, 1, 1, 2);
    play($tournament, $bravo, $charlie, 0, 3, 3);
    play($tournament, $bravo, $alpha, 1, 0, 4);
    play($tournament, $charlie, $alpha, 0, 1, 5);
    play($tournament, $charlie, $bravo, 2, 2, 6);

    $rows = collect(app(StandingsService::class)->calculate($tournament->fresh()))->keyBy('team_id');
    $alphaRow = $rows[$alpha->id];

    expect($alphaRow['played'])->toBe(4)
        ->and($alphaRow['won'])->toBe(2)
        ->and($alphaRow['drawn'])->toBe(1)
        ->and($alphaRow['lost'])->toBe(1)
        ->and($alphaRow['goals_for'])->toBe(4)
        ->and($alphaRow['goals_against'])->toBe(2)
        ->and($alphaRow['goal_difference'])->toBe(2)
        ->and($alphaRow['points'])->toBe(5)
        ->and($alphaRow['form'])->toBe(['W', 'D', 'L', 'W']);
});

it('ranks a head-to-head winner above a team with a better goal difference', function () {
    [$tournament, $teams] = attachLeague(5);
    [$alpha, $bravo, $charlie, $delta, $echo] = $teams->all();
    $alpha->update(['name' => 'Alpha']);
    $bravo->update(['name' => 'Bravo']);

    play($tournament, $alpha, $bravo, 1, 0);
    play($tournament, $charlie, $alpha, 8, 0);
    play($tournament, $bravo, $charlie, 1, 0);
    play($tournament, $bravo, $delta, 1, 0);
    play($tournament, $alpha, $echo, 1, 0);

    $rows = app(StandingsService::class)->calculate($tournament->fresh());
    $order = names($rows);

    expect(array_search('Alpha', $order))->toBeLessThan(array_search('Bravo', $order));
    expect($rows[array_search('Alpha', $order)]['points'])->toBe($rows[array_search('Bravo', $order)]['points']);
    expect($rows[array_search('Alpha', $order)]['goal_difference'])->toBeLessThan($rows[array_search('Bravo', $order)]['goal_difference']);
});

it('uses goal difference when the head-to-head match is level', function () {
    [$tournament, $teams] = attachLeague(3);
    [$alpha, $bravo, $charlie] = $teams->all();
    $alpha->update(['name' => 'Alpha']);
    $bravo->update(['name' => 'Bravo']);

    play($tournament, $alpha, $bravo, 1, 1);
    play($tournament, $alpha, $charlie, 3, 0);
    play($tournament, $bravo, $charlie, 1, 0);

    $order = names(app(StandingsService::class)->calculate($tournament->fresh()));

    expect(array_search('Alpha', $order))->toBeLessThan(array_search('Bravo', $order));
});

it('uses goals scored when points and goal difference are level', function () {
    [$tournament, $teams] = attachLeague(4);
    [$alpha, $bravo, $charlie, $delta] = $teams->all();
    $alpha->update(['name' => 'Alpha']);
    $bravo->update(['name' => 'Bravo']);

    play($tournament, $alpha, $bravo, 1, 1);
    play($tournament, $alpha, $charlie, 3, 1);
    play($tournament, $bravo, $delta, 2, 0);

    $rows = collect(app(StandingsService::class)->calculate($tournament->fresh()))->keyBy('team_name');

    expect($rows['Alpha']['points'])->toBe($rows['Bravo']['points'])
        ->and($rows['Alpha']['goal_difference'])->toBe($rows['Bravo']['goal_difference'])
        ->and($rows['Alpha']['goals_for'])->toBeGreaterThan($rows['Bravo']['goals_for']);

    $order = names(app(StandingsService::class)->calculate($tournament->fresh()));

    expect(array_search('Alpha', $order))->toBeLessThan(array_search('Bravo', $order));
});

it('breaks a three-way tie on head-to-head goal difference', function () {
    [$tournament, $teams] = attachLeague(3);
    [$alpha, $bravo, $charlie] = $teams->all();
    $alpha->update(['name' => 'Alpha']);
    $bravo->update(['name' => 'Bravo']);
    $charlie->update(['name' => 'Charlie']);

    play($tournament, $alpha, $bravo, 1, 0);
    play($tournament, $bravo, $charlie, 1, 0);
    play($tournament, $charlie, $alpha, 2, 0);

    expect(names(app(StandingsService::class)->calculate($tournament->fresh())))
        ->toBe(['Charlie', 'Bravo', 'Alpha']);
});

it('rebuilds the cached table when a match finishes', function () {
    [$tournament, $teams] = attachLeague(2);
    [$home, $away] = $teams->all();
    $game = play($tournament, $home, $away, 3, 1);

    $service = app(StandingsService::class);
    Cache::put($service->cacheKey($tournament), [['points' => -1]], 60);

    event(new MatchFinished($game->fresh()));

    $cached = Cache::get($service->cacheKey($tournament));

    expect($cached[0]['rows'][0]['points'])->toBe(3)
        ->and($cached[0]['rows'][0]['goals_for'])->toBe(3);
});

it('queues the standings listener', function () {
    Queue::fake();

    [$tournament, $teams] = attachLeague(2);
    $game = play($tournament, $teams[0], $teams[1], 1, 0);

    event(new MatchFinished($game));

    Queue::assertPushed(CallQueuedListener::class, function (CallQueuedListener $job) {
        return $job->class === RefreshStandings::class;
    });
});

it('ignores own goals in the scorers list and counts penalties', function () {
    [$tournament, $teams] = attachLeague(2);
    $scorer = Player::factory()->create([
        'team_id' => $teams[0]->id,
        'squad_number' => 9,
        'position' => PlayerPosition::Forward,
    ]);
    $other = Player::factory()->create([
        'team_id' => $teams[1]->id,
        'squad_number' => 4,
        'position' => PlayerPosition::Defender,
    ]);
    $game = play($tournament, $teams[0], $teams[1], 2, 1);

    $game->events()->create(['player_id' => $scorer->id, 'type' => MatchEventType::Goal, 'minute' => 10, 'assist_player_id' => null]);
    $game->events()->create(['player_id' => $scorer->id, 'type' => MatchEventType::Penalty, 'minute' => 40]);
    $game->events()->create(['player_id' => $other->id, 'type' => MatchEventType::OwnGoal, 'minute' => 70]);

    $scorers = app(StatisticsService::class)->topScorers($tournament);

    expect($scorers)->toHaveCount(1)
        ->and($scorers[0]['player_id'])->toBe($scorer->id)
        ->and($scorers[0]['goals'])->toBe(2)
        ->and($scorers[0]['penalties'])->toBe(1);
});
