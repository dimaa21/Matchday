<?php

namespace App\Services;

class FixtureGenerator
{
    /**
     * Circle-method round robin. Odd squads get one bye per round.
     *
     * @param  list<int>  $teamIds
     * @return list<list<array<string, mixed>>>
     */
    public function roundRobin(array $teamIds, bool $doubleRound = false): array
    {
        $ids = array_values($teamIds);

        if (count($ids) < 2) {
            return [];
        }

        if (count($ids) % 2 === 1) {
            $ids[] = null;
        }

        $count = count($ids);
        $rounds = $count - 1;
        $half = intdiv($count, 2);
        $fixed = $ids[0];
        $rotating = array_slice($ids, 1);
        $firstLeg = [];

        for ($round = 1; $round <= $rounds; $round++) {
            $row = array_merge([$fixed], $rotating);
            $pairings = [];

            for ($index = 0; $index < $half; $index++) {
                $home = $row[$index];
                $away = $row[$count - 1 - $index];

                if ($round % 2 === 0) {
                    [$home, $away] = [$away, $home];
                }

                $pairings[] = $this->pairing($round, $home, $away);
            }

            $firstLeg[] = $pairings;
            $last = array_pop($rotating);
            array_unshift($rotating, $last);
        }

        if (! $doubleRound) {
            return $firstLeg;
        }

        $secondLeg = [];

        foreach ($firstLeg as $pairings) {
            $flipped = [];

            foreach ($pairings as $pairing) {
                $flipped[] = $this->pairing(
                    $pairing['round'] + $rounds,
                    $pairing['away_team_id'],
                    $pairing['home_team_id'],
                );
            }

            $secondLeg[] = $flipped;
        }

        return array_merge($firstLeg, $secondLeg);
    }

    /**
     * Single-elimination bracket. Missing slots become first-round byes for the top seeds.
     *
     * @param  list<int>  $teamIds
     * @return list<array<string, mixed>>
     */
    public function knockout(array $teamIds): array
    {
        $teamIds = array_values($teamIds);
        $count = count($teamIds);

        if ($count < 2) {
            return [];
        }

        $size = 1;

        while ($size < $count) {
            $size *= 2;
        }

        $slots = [];

        foreach ($this->seedOrder($size) as $seed) {
            $slots[] = $teamIds[$seed - 1] ?? null;
        }

        $rounds = 0;
        $cursor = $size;

        while ($cursor > 1) {
            $cursor = intdiv($cursor, 2);
            $rounds++;
        }

        $matches = [];

        for ($round = 1; $round <= $rounds; $round++) {
            $matchCount = intdiv($size, 2 ** $round);

            for ($slot = 0; $slot < $matchCount; $slot++) {
                $home = null;
                $away = null;
                $isBye = false;
                $winner = null;

                if ($round === 1) {
                    $home = $slots[$slot * 2];
                    $away = $slots[$slot * 2 + 1];
                    $isBye = $home === null || $away === null;
                    $winner = $isBye ? ($home ?? $away) : null;

                    if ($isBye) {
                        $home = $home ?? $away;
                        $away = null;
                    }
                }

                $matches[] = [
                    'round' => $round,
                    'bracket_slot' => $slot,
                    'home_team_id' => $home,
                    'away_team_id' => $away,
                    'is_bye' => $isBye,
                    'winner_team_id' => $winner,
                    'next_round' => $round < $rounds ? $round + 1 : null,
                    'next_slot' => $round < $rounds ? intdiv($slot, 2) : null,
                    'next_side' => $round < $rounds ? ($slot % 2 === 0 ? 'home' : 'away') : null,
                    'group_name' => null,
                ];
            }
        }

        return $matches;
    }

    /**
     * @return list<int>
     */
    public function seedOrder(int $size): array
    {
        $order = [1];

        while (count($order) < $size) {
            $next = [];
            $sum = count($order) * 2 + 1;

            foreach ($order as $seed) {
                $next[] = $seed;
                $next[] = $sum - $seed;
            }

            $order = $next;
        }

        return $order;
    }

    /**
     * @return array<string, mixed>
     */
    private function pairing(int $round, ?int $home, ?int $away): array
    {
        $isBye = $home === null || $away === null;

        if ($isBye) {
            $home = $home ?? $away;
            $away = null;
        }

        return [
            'round' => $round,
            'bracket_slot' => null,
            'home_team_id' => $home,
            'away_team_id' => $away,
            'is_bye' => $isBye,
            'winner_team_id' => $isBye ? $home : null,
            'next_round' => null,
            'next_slot' => null,
            'next_side' => null,
            'group_name' => null,
        ];
    }
}
