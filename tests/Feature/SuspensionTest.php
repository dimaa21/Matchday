<?php

use App\Enums\MatchEventType;
use App\Enums\MatchStatus;
use App\Enums\PlayerPosition;
use App\Models\Game;
use App\Models\Player;
use App\Models\Team;
use App\Models\Tournament;
use App\Services\SuspensionService;

function suspensionWorld(int $rounds = 4): array
{
    $team = Team::factory()->create();
    $opponent = Team::factory()->create();
    $tournament = Tournament::factory()->create();
    $tournament->teams()->attach([$team->id, $opponent->id]);
    $player = Player::factory()->create([
        'team_id' => $team->id,
        'squad_number' => 10,
        'position' => PlayerPosition::Forward,
    ]);

    $games = collect(range(1, $rounds))->map(fn (int $round) => Game::factory()->create([
        'tournament_id' => $tournament->id,
        'round' => $round,
        'home_team_id' => $round % 2 === 0 ? $opponent->id : $team->id,
        'away_team_id' => $round % 2 === 0 ? $team->id : $opponent->id,
        'status' => MatchStatus::Finished,
        'kickoff_at' => now()->addDays($round),
    ]));

    return [$player, $games, $tournament, $team];
}

it('suspends a player for the next match after a red card', function () {
    [$player, $games] = suspensionWorld();
    $games[0]->events()->create(['player_id' => $player->id, 'type' => MatchEventType::Red, 'minute' => 60]);
    $games[1]->update(['status' => MatchStatus::Scheduled]);
    $games[2]->update(['status' => MatchStatus::Scheduled]);

    $service = app(SuspensionService::class);

    expect($service->banFor($player, $games[1]->fresh()))->toBe('red')
        ->and($service->isSuspended($player, $games[2]->fresh()))->toBeFalse();
});

it('suspends a player for the match after every third yellow card', function () {
    [$player, $games] = suspensionWorld();

    foreach ([0, 1, 2] as $index) {
        $games[$index]->events()->create([
            'player_id' => $player->id,
            'type' => MatchEventType::Yellow,
            'minute' => 20 + $index,
        ]);
    }

    $games[3]->update(['status' => MatchStatus::Scheduled]);

    expect(app(SuspensionService::class)->banFor($player, $games[3]->fresh()))->toBe('accumulated_yellows');
});

it('does not suspend a player who has only two yellow cards', function () {
    [$player, $games] = suspensionWorld(3);

    $games[0]->events()->create(['player_id' => $player->id, 'type' => MatchEventType::Yellow, 'minute' => 12]);
    $games[1]->events()->create(['player_id' => $player->id, 'type' => MatchEventType::Yellow, 'minute' => 44]);
    $games[2]->update(['status' => MatchStatus::Scheduled]);

    expect(app(SuspensionService::class)->isSuspended($player, $games[2]->fresh()))->toBeFalse();
});

it('stacks a red card and a third-yellow ban across the next two matches', function () {
    [$player, $games] = suspensionWorld(4);

    foreach (range(1, 3) as $minute) {
        $games[0]->events()->create([
            'player_id' => $player->id,
            'type' => MatchEventType::Yellow,
            'minute' => $minute,
        ]);
    }

    $games[0]->events()->create(['player_id' => $player->id, 'type' => MatchEventType::Red, 'minute' => 80]);
    $games[1]->update(['status' => MatchStatus::Scheduled]);
    $games[2]->update(['status' => MatchStatus::Scheduled]);
    $games[3]->update(['status' => MatchStatus::Scheduled]);

    $service = app(SuspensionService::class);

    expect($service->banFor($player, $games[1]->fresh()))->toBe('red')
        ->and($service->banFor($player, $games[2]->fresh()))->toBe('accumulated_yellows')
        ->and($service->isSuspended($player, $games[3]->fresh()))->toBeFalse();
});

it('ignores cards from another tournament', function () {
    [$player, $games, $tournament] = suspensionWorld(2);
    $other = Tournament::factory()->create();
    $other->teams()->attach([$player->team_id, Team::factory()->create()->id]);
    $foreign = Game::factory()->finished(1, 0)->create([
        'tournament_id' => $other->id,
        'round' => 1,
        'home_team_id' => $player->team_id,
        'away_team_id' => $other->teams()->where('teams.id', '!=', $player->team_id)->first()->id,
    ]);
    $foreign->events()->create(['player_id' => $player->id, 'type' => MatchEventType::Red, 'minute' => 15]);
    $games[1]->update(['status' => MatchStatus::Scheduled]);

    expect(app(SuspensionService::class)->isSuspended($player, $games[1]->fresh()))->toBeFalse();
});
