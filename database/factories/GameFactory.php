<?php

namespace Database\Factories;

use App\Enums\MatchStatus;
use App\Models\Game;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Game>
 */
class GameFactory extends Factory
{
    protected $model = Game::class;

    public function definition(): array
    {
        return [
            'tournament_id' => Tournament::factory(),
            'round' => 1,
            'home_team_id' => Team::factory(),
            'away_team_id' => Team::factory(),
            'status' => MatchStatus::Scheduled,
            'home_score' => 0,
            'away_score' => 0,
            'is_bye' => false,
            'kickoff_at' => now(),
            'venue' => fake()->city().' Арена',
        ];
    }

    public function finished(int $home = 1, int $away = 0): static
    {
        return $this->state(fn () => [
            'status' => MatchStatus::Finished,
            'home_score' => $home,
            'away_score' => $away,
        ]);
    }

    public function live(): static
    {
        return $this->state(fn () => [
            'status' => MatchStatus::Live,
        ]);
    }
}
