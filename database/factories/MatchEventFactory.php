<?php

namespace Database\Factories;

use App\Enums\MatchEventType;
use App\Models\Game;
use App\Models\MatchEvent;
use App\Models\Player;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MatchEvent>
 */
class MatchEventFactory extends Factory
{
    protected $model = MatchEvent::class;

    public function definition(): array
    {
        return [
            'match_id' => Game::factory(),
            'player_id' => Player::factory(),
            'type' => MatchEventType::Goal,
            'minute' => fake()->numberBetween(1, 90),
        ];
    }
}
