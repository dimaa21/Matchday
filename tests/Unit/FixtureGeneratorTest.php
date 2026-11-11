<?php

use App\Services\FixtureGenerator;

function flattenPairings(array $rounds): array
{
    $pairings = [];

    foreach ($rounds as $round) {
        foreach ($round as $pairing) {
            $pairings[] = $pairing;
        }
    }

    return $pairings;
}

function realPairs(array $rounds): array
{
    return array_values(array_filter(
        flattenPairings($rounds),
        fn (array $pairing) => ! $pairing['is_bye'],
    ));
}

it('gives every team one game against every other team when the squad is even', function () {
    $teams = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
    $rounds = (new FixtureGenerator)->roundRobin($teams);

    expect($rounds)->toHaveCount(9);

    foreach ($rounds as $round) {
        $seen = [];

        foreach ($round as $pairing) {
            expect($pairing['is_bye'])->toBeFalse();
            expect($seen)->not->toContain($pairing['home_team_id']);
            expect($seen)->not->toContain($pairing['away_team_id']);
            $seen[] = $pairing['home_team_id'];
            $seen[] = $pairing['away_team_id'];
        }

        expect($seen)->toHaveCount(10);
    }

    $keys = array_map(
        fn (array $pairing) => min($pairing['home_team_id'], $pairing['away_team_id']).'-'.max($pairing['home_team_id'], $pairing['away_team_id']),
        realPairs($rounds),
    );

    expect($keys)->toHaveCount(45);
    expect(array_unique($keys))->toHaveCount(45);
});

it('schedules a bye each round when the squad is odd and still pairs everyone once', function () {
    $teams = [1, 2, 3, 4, 5];
    $rounds = (new FixtureGenerator)->roundRobin($teams);
    $byes = array_fill_keys($teams, 0);

    expect($rounds)->toHaveCount(5);

    foreach ($rounds as $round) {
        $seen = [];
        $byeCount = 0;

        foreach ($round as $pairing) {
            if ($pairing['is_bye']) {
                $byeCount++;
                $byes[$pairing['home_team_id']]++;
                expect($pairing['away_team_id'])->toBeNull();

                continue;
            }

            expect($seen)->not->toContain($pairing['home_team_id']);
            expect($seen)->not->toContain($pairing['away_team_id']);
            $seen[] = $pairing['home_team_id'];
            $seen[] = $pairing['away_team_id'];
        }

        expect($byeCount)->toBe(1);
        expect($seen)->toHaveCount(4);
    }

    expect($byes)->each->toBe(1);

    $keys = array_map(
        fn (array $pairing) => min($pairing['home_team_id'], $pairing['away_team_id']).'-'.max($pairing['home_team_id'], $pairing['away_team_id']),
        realPairs($rounds),
    );

    expect(array_unique($keys))->toHaveCount(10);
    expect($keys)->toHaveCount(10);
});

it('reverses home and away in the second leg of a double round robin', function () {
    $rounds = (new FixtureGenerator)->roundRobin([1, 2, 3, 4], true);
    $directed = [];

    expect($rounds)->toHaveCount(6);

    foreach (realPairs($rounds) as $pairing) {
        $directed[] = $pairing['home_team_id'].'-'.$pairing['away_team_id'];
    }

    expect($directed)->toHaveCount(12);

    foreach (['1-2', '1-3', '1-4', '2-3', '2-4', '3-4'] as $pair) {
        [$home, $away] = explode('-', $pair);
        expect($directed)->toContain($home.'-'.$away);
        expect($directed)->toContain($away.'-'.$home);
    }
});

it('builds a power-of-two cup and gives byes to the top seeds', function () {
    $generator = new FixtureGenerator;
    $matches = $generator->knockout([1, 2, 3, 4, 5, 6]);

    expect($matches)->toHaveCount(7);
    expect(collect($matches)->where('round', 1))->toHaveCount(4);
    expect(collect($matches)->where('is_bye', true))->toHaveCount(2);

    $byeWinners = collect($matches)->where('is_bye', true)->pluck('winner_team_id')->sort()->values()->all();

    expect($byeWinners)->toBe([1, 2]);

    $final = collect($matches)->firstWhere('next_round', null);

    expect($final['round'])->toBe(3);
    expect(collect($matches)->where('round', '<', 3)->every(fn (array $match) => $match['next_round'] !== null))->toBeTrue();
});
