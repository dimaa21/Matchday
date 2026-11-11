<?php

use App\Enums\MatchEventType;
use App\Enums\MatchStatus;
use App\Enums\PlayerPosition;
use App\Models\Game;
use App\Models\Player;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use App\Services\StandingsService;

it('lets guests read the public tournament and blocks the admin desk', function () {
    $tournament = Tournament::factory()->create();

    $this->get(route('tournaments.show', $tournament))->assertOk();
    $this->get(route('admin.tournaments.index'))->assertRedirect(route('login'));
});

it('stops captains and referees from managing tournaments', function () {
    $captain = User::factory()->captain()->create();
    $referee = User::factory()->referee()->create();

    $this->actingAs($captain)->post(route('admin.tournaments.store'), [])->assertForbidden();
    $this->actingAs($referee)->post(route('admin.tournaments.fixtures', Tournament::factory()->create()))->assertForbidden();
});

it('lets a captain edit only their own player', function () {
    $team = Team::factory()->create();
    $other = Team::factory()->create();
    $captain = User::factory()->captain($team)->create();
    $own = Player::factory()->create(['team_id' => $team->id, 'squad_number' => 7, 'position' => PlayerPosition::Forward]);
    $foreign = Player::factory()->create(['team_id' => $other->id, 'squad_number' => 8, 'position' => PlayerPosition::Forward]);

    $this->actingAs($captain)->patch(route('captain.players.update', $own), [
        'name' => 'New name',
        'squad_number' => 7,
        'position' => PlayerPosition::Forward->value,
    ])->assertRedirect();

    expect($own->fresh()->name)->toBe('New name');

    $this->actingAs($captain)->patch(route('captain.players.update', $foreign), [
        'name' => 'Someone else',
        'squad_number' => 8,
        'position' => PlayerPosition::Forward->value,
    ])->assertForbidden();
});

it('lets the assigned referee record a live goal and rejects everyone else', function () {
    $tournament = Tournament::factory()->create();
    $home = Team::factory()->create();
    $away = Team::factory()->create();
    $referee = User::factory()->referee()->create();
    $other = User::factory()->referee()->create();
    $scorer = Player::factory()->create([
        'team_id' => $home->id,
        'squad_number' => 9,
        'position' => PlayerPosition::Forward,
    ]);
    $game = Game::factory()->live()->create([
        'tournament_id' => $tournament->id,
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
        'referee_id' => $referee->id,
    ]);

    $payload = [
        'type' => MatchEventType::Goal->value,
        'player_id' => $scorer->id,
        'minute' => 12,
    ];

    $this->actingAs($other)->post(route('referee.matches.events.store', $game), $payload)->assertForbidden();

    $this->actingAs($referee)->post(route('referee.matches.events.store', $game), $payload)->assertRedirect();

    expect($game->fresh()->home_score)->toBe(1);

    $ownGoal = Player::factory()->create([
        'team_id' => $away->id,
        'squad_number' => 4,
        'position' => PlayerPosition::Defender,
    ]);

    $this->actingAs($referee)->post(route('referee.matches.events.store', $game), [
        'type' => MatchEventType::OwnGoal->value,
        'player_id' => $ownGoal->id,
        'minute' => 40,
    ])->assertRedirect();

    expect($game->fresh()->home_score)->toBe(2)
        ->and($game->fresh()->away_score)->toBe(0);
});

it('refuses match events before the referee starts the match', function () {
    $referee = User::factory()->referee()->create();
    $home = Team::factory()->create();
    $player = Player::factory()->create(['team_id' => $home->id, 'squad_number' => 10, 'position' => PlayerPosition::Forward]);
    $game = Game::factory()->create([
        'home_team_id' => $home->id,
        'away_team_id' => Team::factory(),
        'referee_id' => $referee->id,
        'status' => MatchStatus::Scheduled,
    ]);

    $this->actingAs($referee)->post(route('referee.matches.events.store', $game), [
        'type' => MatchEventType::Goal->value,
        'player_id' => $player->id,
        'minute' => 1,
    ])->assertSessionHasErrors('match');
});

it('finishes a live match and refreshes the table', function () {
    $tournament = Tournament::factory()->create();
    [$home, $away] = Team::factory()->count(2)->create();
    $tournament->teams()->attach([$home->id, $away->id]);
    $referee = User::factory()->referee()->create();
    $game = Game::factory()->live()->create([
        'tournament_id' => $tournament->id,
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
        'referee_id' => $referee->id,
        'home_score' => 2,
        'away_score' => 1,
    ]);

    $this->actingAs($referee)->post(route('referee.matches.finish', $game))->assertRedirect();

    expect($game->fresh()->status)->toBe(MatchStatus::Finished);

    $table = app(StandingsService::class)->cached($tournament->fresh());

    expect($table[0]['rows'][0]['team_id'])->toBe($home->id)
        ->and($table[0]['rows'][0]['points'])->toBe(3);
});
