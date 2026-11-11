<?php

use App\Enums\MatchStatus;
use App\Enums\TournamentFormat;
use App\Listeners\AdvanceKnockoutWinner;
use App\Models\Team;
use App\Models\Tournament;
use App\Services\FixtureScheduler;
use Illuminate\Support\Carbon;

it('persists a bye for every round of an odd league', function () {
    $tournament = Tournament::factory()->create(['legs' => 1]);
    $teams = Team::factory()->count(5)->create();
    $tournament->teams()->attach($teams->pluck('id'));

    $created = app(FixtureScheduler::class)->generate($tournament, Carbon::parse('2026-03-07 16:00:00'));

    expect($created)->toBe(15)
        ->and($tournament->games()->where('is_bye', true)->count())->toBe(5)
        ->and($tournament->games()->where('is_bye', false)->count())->toBe(10);

    $pairKeys = $tournament->games()->where('is_bye', false)->get()->map(
        fn ($game) => min($game->home_team_id, $game->away_team_id).'-'.max($game->home_team_id, $game->away_team_id),
    );

    expect($pairKeys->unique())->toHaveCount(10);
});

it('advances the winner of a knockout match and auto-advances byes', function () {
    $tournament = Tournament::factory()->create(['format' => TournamentFormat::Cup, 'legs' => 1]);
    $teams = Team::factory()->count(6)->create();
    $tournament->teams()->attach($teams->pluck('id'));

    app(FixtureScheduler::class)->generate($tournament, Carbon::parse('2026-04-04 16:00:00'));

    $byes = $tournament->games()->where('is_bye', true)->get();

    expect($byes)->toHaveCount(2);

    foreach ($byes as $bye) {
        $next = $bye->nextMatch()->first();
        $column = $bye->next_slot === 'away' ? 'away_team_id' : 'home_team_id';

        expect($next->{$column})->toBe($bye->home_team_id);
    }

    $quarter = $tournament->games()->where('is_bye', false)->where('round', 1)->first();
    $quarter->update([
        'status' => MatchStatus::Finished,
        'home_score' => 2,
        'away_score' => 0,
    ]);

    app(AdvanceKnockoutWinner::class)->advance($quarter->fresh());

    $next = $quarter->nextMatch()->first()->fresh();
    $column = $quarter->next_slot === 'away' ? 'away_team_id' : 'home_team_id';

    expect($next->{$column})->toBe($quarter->home_team_id);
});

it('does not advance a drawn cup match', function () {
    $tournament = Tournament::factory()->create(['format' => TournamentFormat::Cup]);
    $teams = Team::factory()->count(4)->create();
    $tournament->teams()->attach($teams->pluck('id'));
    app(FixtureScheduler::class)->generate($tournament);

    $semi = $tournament->games()->where('round', 1)->first();
    $semi->update(['status' => MatchStatus::Finished, 'home_score' => 1, 'away_score' => 1]);
    app(AdvanceKnockoutWinner::class)->advance($semi->fresh());

    $final = $semi->nextMatch()->first()->fresh();
    $column = $semi->next_slot === 'away' ? 'away_team_id' : 'home_team_id';

    expect($final->{$column})->toBeNull();
});

it('builds group games and a crossover playoff', function () {
    $tournament = Tournament::factory()->create([
        'format' => TournamentFormat::GroupsPlayoff,
        'legs' => 1,
    ]);
    $teams = Team::factory()->count(4)->create();
    $tournament->teams()->attach([
        $teams[0]->id => ['group_name' => 'A'],
        $teams[1]->id => ['group_name' => 'A'],
        $teams[2]->id => ['group_name' => 'B'],
        $teams[3]->id => ['group_name' => 'B'],
    ]);

    app(FixtureScheduler::class)->generate($tournament);

    expect($tournament->games()->where('group_name', 'A')->where('is_bye', false)->count())->toBe(1)
        ->and($tournament->games()->where('group_name', 'B')->where('is_bye', false)->count())->toBe(1);

    $tournament->games()->where('is_bye', false)->update([
        'status' => MatchStatus::Finished,
        'home_score' => 1,
        'away_score' => 0,
    ]);

    $created = app(FixtureScheduler::class)->generatePlayoff($tournament->fresh());

    expect($created)->toBe(3);

    $semis = $tournament->games()->whereNull('group_name')->whereNotNull('home_team_id')->whereNotNull('away_team_id')->get();

    expect($semis)->not->toBeEmpty();
});
