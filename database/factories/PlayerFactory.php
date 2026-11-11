<?php

namespace Database\Factories;

use App\Enums\PlayerPosition;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Player>
 */
class PlayerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'name' => fake()->name(),
            'squad_number' => fake()->numberBetween(1, 99),
            'position' => fake()->randomElement(PlayerPosition::cases()),
        ];
    }
}
