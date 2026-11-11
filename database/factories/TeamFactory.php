<?php

namespace Database\Factories;

use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Team>
 */
class TeamFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->city().' '.fake()->randomElement(['United', 'Athletic', 'City']);

        return [
            'name' => $name,
            'slug' => Str::slug(Str::transliterate($name)).'-'.fake()->unique()->numerify('###'),
            'city' => fake()->city(),
            'primary_color' => fake()->hexColor(),
            'secondary_color' => '#101a24',
        ];
    }
}
