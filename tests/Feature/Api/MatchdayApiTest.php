<?php

use App\Enums\MatchEventType;
use App\Enums\PlayerPosition;
use App\Models\Game;
use App\Models\Player;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('returns standings, a round of fixtures, a match and the scorers', function () {
    $tournament = Tournament::factory()->create(['name' => 'Matchday League', 'slug' => 'matchday-liga']);
    $home = Team::factory()->create(['name' => 'Gale']);
    $away = Team::factory()->create(['name' => 'Wave']);
    $tournament->teams()->attach([$home->id, $away->id]);
    $scorer = Player::factory()->create([
        'team_id' => $home->id,
        'name' => 'Owen Cole',
        'squad_number' => 9,
        'position' => PlayerPosition::Forward,
    ]);
    $game = Game::factory()->finished(2, 0)->create([
        'tournament_id' => $tournament->id,
        'round' => 3,
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
        'venue' => 'Kyiv Arena',
    ]);
    $game->events()->create([
        'player_id' => $scorer->id,
        'type' => MatchEventType::Goal,
        'minute' => 18,
    ]);

    $this->getJson('/api/tournaments/matchday-liga/standings')
        ->assertOk()
        ->assertJsonPath('tables.0.rows.0.team_name', 'Gale')
        ->assertJsonPath('tables.0.rows.0.points', 3);

    $this->getJson('/api/tournaments/matchday-liga/fixtures?round=3')
        ->assertOk()
        ->assertJsonPath('fixtures.0.venue', 'Kyiv Arena')
        ->assertJsonPath('fixtures.0.home_score', 2);

    $this->getJson('/api/tournaments/matchday-liga/fixtures?round=9')
        ->assertOk()
        ->assertJsonCount(0, 'fixtures');

    $this->getJson('/api/matches/'.$game->id)
        ->assertOk()
        ->assertJsonPath('events.0.player', 'Owen Cole')
        ->assertJsonPath('home_score', 2);

    $this->getJson('/api/tournaments/matchday-liga/top-scorers')
        ->assertOk()
        ->assertJsonPath('scorers.0.player_name', 'Owen Cole')
        ->assertJsonPath('scorers.0.goals', 1);
});

it('issues a sanctum token and rejects a bad password', function () {
    $user = User::factory()->admin()->create([
        'email' => 'admin@matchday.test',
        'password' => 'password',
    ]);

    $this->postJson('/api/login', [
        'email' => 'admin@matchday.test',
        'password' => 'nope',
    ])->assertUnprocessable();

    $token = $this->postJson('/api/login', [
        'email' => 'admin@matchday.test',
        'password' => 'password',
        'device_name' => 'phone',
    ])->assertOk()->json('token');

    Sanctum::actingAs($user);

    $this->getJson('/api/user')->assertOk()->assertJsonPath('email', 'admin@matchday.test');

    expect($token)->toBeString()->not->toBeEmpty();
});
